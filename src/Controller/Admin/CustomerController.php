<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/customers', name: 'admin_customers_')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CustomerController extends AbstractController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private SubscriptionPlanRepository $planRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $customers = $this->customerRepository->findBy([], ['createdAt' => 'DESC']);
        $stats = $this->customerRepository->getStatistics();

        return $this->render('admin/customers/index.html.twig', [
            'customers' => $customers,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    public function view(Customer $customer): Response
    {
        return $this->render('admin/customers/view.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $customer = new Customer();
            $customer->setCompanyName($request->request->get('company_name'));
            $customer->setEmail($request->request->get('email'));
            $customer->setPhone($request->request->get('phone'));
            $customer->setTaxOffice($request->request->get('tax_office'));
            $customer->setTaxNumber($request->request->get('tax_number'));
            $customer->setAddress($request->request->get('address'));
            $customer->setCity($request->request->get('city'));
            $customer->setDistrict($request->request->get('district'));
            $customer->setPostalCode($request->request->get('postal_code'));
            $customer->setCountry($request->request->get('country', 'TR'));
            $customer->setIsActive($request->request->get('is_active', true));
            
            // Set subscription plan if provided
            if ($planId = $request->request->get('current_plan_id')) {
                $plan = $this->planRepository->find($planId);
                if ($plan) {
                    $customer->setCurrentPlan($plan);
                }
            }
            
            // Set subscription dates if provided
            if ($startDate = $request->request->get('subscription_start_date')) {
                $customer->setSubscriptionStartDate(new \DateTime($startDate));
            }
            if ($endDate = $request->request->get('subscription_end_date')) {
                $customer->setSubscriptionEndDate(new \DateTime($endDate));
            }

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Müşteri başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_customers_view', ['id' => $customer->getId()]);
        }

        // Get all plans including inactive ones (for custom packages)
        $plans = $this->planRepository->findBy([], ['priority' => 'DESC']);

        return $this->render('admin/customers/create.html.twig', [
            'plans' => $plans,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer): Response
    {
        if ($request->isMethod('POST')) {
            $customer->setCompanyName($request->request->get('company_name'));
            $customer->setEmail($request->request->get('email'));
            $customer->setPhone($request->request->get('phone'));
            $customer->setTaxOffice($request->request->get('tax_office'));
            $customer->setTaxNumber($request->request->get('tax_number'));
            $customer->setAddress($request->request->get('address'));
            $customer->setCity($request->request->get('city'));
            $customer->setDistrict($request->request->get('district'));
            $customer->setPostalCode($request->request->get('postal_code'));
            $customer->setCountry($request->request->get('country', 'TR'));
            $customer->setIsActive($request->request->get('is_active', false));
            
            // Update subscription plan if provided
            if ($planId = $request->request->get('current_plan_id')) {
                $plan = $this->planRepository->find($planId);
                if ($plan) {
                    $customer->setCurrentPlan($plan);
                }
            }
            
            // Update subscription dates if provided
            if ($startDate = $request->request->get('subscription_start_date')) {
                $customer->setSubscriptionStartDate(new \DateTime($startDate));
            }
            if ($endDate = $request->request->get('subscription_end_date')) {
                $customer->setSubscriptionEndDate(new \DateTime($endDate));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Müşteri bilgileri başarıyla güncellendi.');
            return $this->redirectToRoute('admin_customers_view', ['id' => $customer->getId()]);
        }

        // Get all plans including inactive ones (for custom packages)
        $plans = $this->planRepository->findBy([], ['priority' => 'DESC']);

        return $this->render('admin/customers/edit.html.twig', [
            'customer' => $customer,
            'plans' => $plans,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Customer $customer): Response
    {
        // Check if customer has active subscriptions or shops
        if ($customer->getShops()->count() > 0) {
            $this->addFlash('error', 'Bu müşteriye ait mağazalar var. Önce mağazaları silin.');
            return $this->redirectToRoute('admin_customers_view', ['id' => $customer->getId()]);
        }

        $this->entityManager->remove($customer);
        $this->entityManager->flush();

        $this->addFlash('success', 'Müşteri başarıyla silindi.');
        return $this->redirectToRoute('admin_customers_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(Customer $customer): Response
    {
        $customer->setIsActive(!$customer->isActive());
        $this->entityManager->flush();

        $status = $customer->isActive() ? 'aktif' : 'pasif';
        $this->addFlash('success', "Müşteri durumu {$status} olarak güncellendi.");
        
        return $this->redirectToRoute('admin_customers_view', ['id' => $customer->getId()]);
    }
}
