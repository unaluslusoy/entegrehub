<?php

namespace App\Controller\Admin;

use App\Entity\UserSubscription;
use App\Repository\UserSubscriptionRepository;
use App\Repository\UserRepository;
use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/subscriptions')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private UserSubscriptionRepository $subscriptionRepository,
        private UserRepository $userRepository,
        private SubscriptionPlanRepository $planRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'admin_subscriptions', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $status = $request->query->get('status', 'all');
        
        $criteria = [];
        if ($status !== 'all') {
            $criteria['status'] = $status;
        }
        
        $subscriptions = $this->subscriptionRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );
        
        $total = $this->subscriptionRepository->count($criteria);
        $pages = ceil($total / $limit);

        return $this->render('admin/subscription/index.html.twig', [
            'subscriptions' => $subscriptions,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_status' => $status,
        ]);
    }

    #[Route('/create', name: 'admin_subscriptions_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $user = $this->userRepository->find($data['user_id']);
            $plan = $this->planRepository->find($data['plan_id']);
            
            if (!$user || !$plan) {
                $this->addFlash('error', 'Kullanıcı veya paket bulunamadı.');
                return $this->redirectToRoute('admin_subscriptions_create');
            }

            // Check if user already has an active subscription
            $existingSubscription = $this->subscriptionRepository->findOneBy([
                'user' => $user,
                'status' => 'active'
            ]);

            if ($existingSubscription) {
                $this->addFlash('error', 'Kullanıcının zaten aktif bir aboneliği var.');
                return $this->redirectToRoute('admin_subscriptions_create');
            }

            $subscription = new UserSubscription();
            $subscription->setUser($user);
            $subscription->setPlan($plan);
            $subscription->setStatus('active');
            $subscription->setBillingPeriod($data['billing_period'] ?? 'monthly');
            
            $startDate = new \DateTime($data['start_date'] ?? 'now');
            $subscription->setStartDate($startDate);
            
            // Calculate end date based on billing period
            $endDate = clone $startDate;
            if ($data['billing_period'] === 'yearly') {
                $endDate->modify('+1 year');
            } else {
                $endDate->modify('+1 month');
            }
            $subscription->setEndDate($endDate);
            
            // Set next billing date
            $subscription->setNextBillingDate(clone $endDate);
            
            $subscription->setAutoRenew(isset($data['auto_renew']));
            $subscription->setPaymentMethod($data['payment_method'] ?? null);
            
            // Trial period
            if (isset($data['is_trial'])) {
                $subscription->setIsTrialPeriod(true);
                $trialStart = new \DateTime();
                $trialEnd = clone $trialStart;
                $trialEnd->modify('+' . ($data['trial_days'] ?? 14) . ' days');
                $subscription->setTrialStartDate($trialStart);
                $subscription->setTrialEndDate($trialEnd);
            }
            
            $subscription->setNotes($data['notes'] ?? null);

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_subscriptions');
        }

        $users = $this->userRepository->findAll();
        $plans = $this->planRepository->findBy(['isActive' => true], ['priority' => 'ASC']);

        return $this->render('admin/subscription/create.html.twig', [
            'users' => $users,
            'plans' => $plans,
        ]);
    }

    #[Route('/{id}', name: 'admin_subscription_detail', methods: ['GET'])]
    public function detail(UserSubscription $subscription): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('admin/subscription/detail.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_subscriptions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserSubscription $subscription): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            if (isset($data['plan_id'])) {
                $plan = $this->planRepository->find($data['plan_id']);
                if ($plan) {
                    $subscription->setPlan($plan);
                }
            }
            
            if (isset($data['status'])) {
                $subscription->setStatus($data['status']);
                
                if ($data['status'] === 'cancelled') {
                    $subscription->setCancelledAt(new \DateTime());
                    $subscription->setCancellationReason($data['cancellation_reason'] ?? null);
                } elseif ($data['status'] === 'suspended') {
                    $subscription->setSuspendedAt(new \DateTime());
                }
            }
            
            if (isset($data['end_date'])) {
                $subscription->setEndDate(new \DateTime($data['end_date']));
            }
            
            $subscription->setAutoRenew(isset($data['auto_renew']));
            $subscription->setPaymentMethod($data['payment_method'] ?? null);
            $subscription->setNotes($data['notes'] ?? null);

            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik başarıyla güncellendi.');
            return $this->redirectToRoute('admin_subscriptions');
        }

        $plans = $this->planRepository->findBy(['isActive' => true], ['priority' => 'ASC']);

        return $this->render('admin/subscription/edit.html.twig', [
            'subscription' => $subscription,
            'plans' => $plans,
        ]);
    }

    #[Route('/{id}/renew', name: 'admin_subscriptions_renew', methods: ['POST'])]
    public function renew(UserSubscription $subscription): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($subscription->getStatus() !== 'active' && $subscription->getStatus() !== 'expired') {
            $this->addFlash('error', 'Sadece aktif veya süresi dolmuş abonelikler yenilenebilir.');
            return $this->redirectToRoute('admin_subscriptions');
        }

        // Extend end date
        $newEndDate = clone $subscription->getEndDate();
        if ($subscription->getBillingPeriod() === 'yearly') {
            $newEndDate->modify('+1 year');
        } else {
            $newEndDate->modify('+1 month');
        }
        
        $subscription->setEndDate($newEndDate);
        $subscription->setNextBillingDate($newEndDate);
        $subscription->setStatus('active');
        $subscription->setLastPaymentDate(new \DateTime());

        $this->entityManager->flush();

        $this->addFlash('success', 'Abonelik başarıyla yenilendi.');
        return $this->redirectToRoute('admin_subscriptions');
    }

    #[Route('/{id}/cancel', name: 'admin_subscriptions_cancel', methods: ['POST'])]
    public function cancel(Request $request, UserSubscription $subscription): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $data = json_decode($request->getContent(), true);
        
        $subscription->setStatus('cancelled');
        $subscription->setCancelledAt(new \DateTime());
        $subscription->setCancellationReason($data['reason'] ?? 'Admin tarafından iptal edildi');
        $subscription->setAutoRenew(false);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Abonelik iptal edildi.',
        ]);
    }

    #[Route('/{id}/suspend', name: 'admin_subscriptions_suspend', methods: ['POST'])]
    public function suspend(UserSubscription $subscription): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $subscription->setStatus('suspended');
        $subscription->setSuspendedAt(new \DateTime());

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Abonelik askıya alındı.',
        ]);
    }

    #[Route('/{id}/activate', name: 'admin_subscriptions_activate', methods: ['POST'])]
    public function activate(UserSubscription $subscription): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $subscription->setStatus('active');
        $subscription->setSuspendedAt(null);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Abonelik aktif edildi.',
        ]);
    }

    #[Route('/{id}/reset-usage', name: 'admin_subscriptions_reset_usage', methods: ['POST'])]
    public function resetUsage(UserSubscription $subscription): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $subscription->setCurrentMonthOrders(0);
        $subscription->setCurrentMonthSms(0);
        $subscription->setCurrentMonthEmails(0);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Kullanım istatistikleri sıfırlandı.',
        ]);
    }
}
