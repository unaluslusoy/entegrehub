<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserSubscriptionRepository;
use App\Repository\SubscriptionPlanRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/subscription')]
#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserSubscriptionRepository $subscriptionRepository,
        private SubscriptionPlanRepository $planRepository,
        private InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Subscription overview & management
     */
    #[Route('', name: 'user_subscription', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get active subscription
        $activeSubscription = $this->subscriptionRepository->findActiveByUser($user);

        // Get all available plans
        $plans = $this->planRepository->findBy(
            ['isActive' => true],
            ['priority' => 'DESC', 'monthlyPrice' => 'ASC']
        );

        // Get subscription history
        $subscriptionHistory = $this->subscriptionRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            10
        );

        // Calculate days remaining
        $daysRemaining = null;
        if ($activeSubscription && $activeSubscription->getEndDate()) {
            $now = new \DateTime();
            $endDate = $activeSubscription->getEndDate();
            $interval = $now->diff($endDate);
            $daysRemaining = $interval->days;
            if ($endDate < $now) {
                $daysRemaining = -$daysRemaining; // Negative if expired
            }
        }

        return $this->render('user/subscription/index.html.twig', [
            'active_subscription' => $activeSubscription,
            'plans' => $plans,
            'subscription_history' => $subscriptionHistory,
            'days_remaining' => $daysRemaining,
        ]);
    }

    /**
     * View available plans for upgrade/downgrade
     */
    #[Route('/plans', name: 'user_subscription_plans', methods: ['GET'])]
    public function plans(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeSubscription = $this->subscriptionRepository->findActiveByUser($user);
        $plans = $this->planRepository->findBy(
            ['isActive' => true],
            ['priority' => 'DESC', 'monthlyPrice' => 'ASC']
        );

        return $this->render('user/subscription/plans.html.twig', [
            'active_subscription' => $activeSubscription,
            'plans' => $plans,
        ]);
    }

    /**
     * Upgrade/downgrade subscription
     */
    #[Route('/change/{id}', name: 'user_subscription_change', methods: ['POST'])]
    public function changeSubscription(int $id, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $plan = $this->planRepository->find($id);
        if (!$plan || !$plan->isIsActive()) {
            $this->addFlash('error', 'Seçtiğiniz paket bulunamadı veya aktif değil.');
            return $this->redirectToRoute('user_subscription_plans');
        }

        $billingCycle = $request->request->get('billing_cycle', 'monthly'); // monthly or yearly

        try {
            // TODO: Implement payment gateway integration
            // For now, just show a message

            $this->addFlash('warning', 'Paket değişikliği için ödeme entegrasyonu tamamlanıyor. Lütfen müşteri temsilcisiyle iletişime geçin.');
            return $this->redirectToRoute('user_subscription');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Abonelik değiştirirken hata oluştu: ' . $e->getMessage());
            return $this->redirectToRoute('user_subscription_plans');
        }
    }

    /**
     * Cancel subscription
     */
    #[Route('/cancel', name: 'user_subscription_cancel', methods: ['POST'])]
    public function cancelSubscription(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeSubscription = $this->subscriptionRepository->findActiveByUser($user);

        if (!$activeSubscription) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aktif aboneliğiniz bulunmuyor.'
            ], 400);
        }

        $reason = $request->request->get('reason', 'Kullanıcı talebi');

        // Set auto_renew to false instead of canceling immediately
        $activeSubscription->setAutoRenew(false);
        $activeSubscription->setCancellationReason($reason);
        $activeSubscription->setCancelledAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Aboneliğiniz iptal edildi. Mevcut dönem sonuna kadar hizmetlerinizi kullanmaya devam edebilirsiniz.'
        ]);
    }

    /**
     * Reactivate subscription
     */
    #[Route('/reactivate', name: 'user_subscription_reactivate', methods: ['POST'])]
    public function reactivateSubscription(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeSubscription = $this->subscriptionRepository->findActiveByUser($user);

        if (!$activeSubscription) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aktif aboneliğiniz bulunmuyor.'
            ], 400);
        }

        $activeSubscription->setAutoRenew(true);
        $activeSubscription->setCancellationReason(null);
        $activeSubscription->setCancelledAt(null);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Aboneliğiniz yeniden aktifleştirildi.'
        ]);
    }

    /**
     * Invoice list
     */
    #[Route('/invoices', name: 'user_subscription_invoices', methods: ['GET'])]
    public function invoices(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $invoices = $this->invoiceRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('user/subscription/invoices.html.twig', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Download invoice PDF
     */
    #[Route('/invoices/{id}/download', name: 'user_subscription_invoice_download', methods: ['GET'])]
    public function downloadInvoice(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $invoice = $this->invoiceRepository->find($id);

        if (!$invoice || $invoice->getUser() !== $user) {
            throw $this->createNotFoundException('Fatura bulunamadı.');
        }

        // TODO: Generate PDF invoice
        $this->addFlash('warning', 'PDF fatura oluşturma özelliği yakında eklenecek.');
        return $this->redirectToRoute('user_subscription_invoices');
    }

    /**
     * Payment history
     */
    #[Route('/payment-history', name: 'user_subscription_payment_history', methods: ['GET'])]
    public function paymentHistory(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get all paid invoices
        $payments = $this->invoiceRepository->findBy(
            ['user' => $user, 'status' => 'paid'],
            ['paidAt' => 'DESC'],
            50
        );

        // Calculate total spent
        $totalSpent = array_reduce($payments, function($carry, $invoice) {
            return $carry + (float) $invoice->getTotalAmount();
        }, 0);

        return $this->render('user/subscription/payment_history.html.twig', [
            'payments' => $payments,
            'total_spent' => $totalSpent,
        ]);
    }

    /**
     * Update payment method
     */
    #[Route('/payment-method', name: 'user_subscription_payment_method', methods: ['GET', 'POST'])]
    public function paymentMethod(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // TODO: Implement payment method update via payment gateway
            $this->addFlash('warning', 'Ödeme yöntemi güncelleme özelliği yakında eklenecek.');
            return $this->redirectToRoute('user_subscription_payment_method');
        }

        return $this->render('user/subscription/payment_method.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Upcoming payments calendar
     */
    #[Route('/upcoming-payments', name: 'user_subscription_upcoming', methods: ['GET'])]
    public function upcomingPayments(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeSubscription = $this->subscriptionRepository->findActiveByUser($user);

        $upcomingPayments = [];
        if ($activeSubscription && $activeSubscription->getAutoRenew()) {
            // Calculate next 12 months of payments
            $endDate = $activeSubscription->getEndDate();
            $plan = $activeSubscription->getPlan();

            $amount = $activeSubscription->getBillingCycle() === 'yearly'
                ? (float) $plan->getYearlyPrice()
                : (float) $plan->getMonthlyPrice();

            for ($i = 0; $i < 12; $i++) {
                $paymentDate = clone $endDate;

                if ($activeSubscription->getBillingCycle() === 'yearly') {
                    $paymentDate->modify('+' . $i . ' year');
                } else {
                    $paymentDate->modify('+' . $i . ' month');
                }

                $upcomingPayments[] = [
                    'date' => $paymentDate,
                    'amount' => $amount,
                    'plan' => $plan->getName(),
                    'billing_cycle' => $activeSubscription->getBillingCycle(),
                ];
            }
        }

        return $this->render('user/subscription/upcoming_payments.html.twig', [
            'active_subscription' => $activeSubscription,
            'upcoming_payments' => $upcomingPayments,
        ]);
    }
}
