<?php

namespace App\Controller\Admin;

use App\Entity\SubscriptionPlan;
use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/plans')]
class PlanController extends AbstractController
{
    public function __construct(
        private SubscriptionPlanRepository $planRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'admin_plans', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $plans = $this->planRepository->findBy([], ['priority' => 'ASC']);

        return $this->render('admin/plan/index.html.twig', [
            'plans' => $plans,
        ]);
    }

    #[Route('/create', name: 'admin_plans_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $plan = new SubscriptionPlan();
            $plan->setCode($data['code']);
            $plan->setName($data['name']);
            $plan->setDescription($data['description'] ?? null);
            $plan->setMonthlyPrice((float)$data['monthly_price']);
            $plan->setYearlyPrice((float)$data['yearly_price']);
            $plan->setMaxShops((int)$data['max_shops']);
            $plan->setMaxOrders((int)$data['max_orders']);
            $plan->setMaxUsers((int)$data['max_users']);
            $plan->setMaxSmsPerMonth(isset($data['max_sms_per_month']) ? (int)$data['max_sms_per_month'] : null);
            $plan->setMaxEmailPerMonth(isset($data['max_email_per_month']) ? (int)$data['max_email_per_month'] : null);
            $plan->setHasApiAccess(isset($data['has_api_access']));
            $plan->setHasAdvancedReports(isset($data['has_advanced_reports']));
            $plan->setHasBarcodeScanner(isset($data['has_barcode_scanner']));
            $plan->setHasAiFeatures(isset($data['has_ai_features']));
            $plan->setHasWhiteLabel(isset($data['has_white_label']));
            $plan->setHasPrioritySupport(isset($data['has_priority_support']));
            $plan->setHasCustomDomain(isset($data['has_custom_domain']));
            $plan->setIsActive(isset($data['is_active']));
            $plan->setIsPopular(isset($data['is_popular']));
            $plan->setPriority((int)($data['priority'] ?? 0));

            $this->entityManager->persist($plan);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik paketi başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_plans');
        }

        return $this->render('admin/plan/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_plans_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SubscriptionPlan $plan): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $plan->setCode($data['code']);
            $plan->setName($data['name']);
            $plan->setDescription($data['description'] ?? null);
            $plan->setMonthlyPrice((float)$data['monthly_price']);
            $plan->setYearlyPrice((float)$data['yearly_price']);
            $plan->setMaxShops((int)$data['max_shops']);
            $plan->setMaxOrders((int)$data['max_orders']);
            $plan->setMaxUsers((int)$data['max_users']);
            $plan->setMaxSmsPerMonth(isset($data['max_sms_per_month']) ? (int)$data['max_sms_per_month'] : null);
            $plan->setMaxEmailPerMonth(isset($data['max_email_per_month']) ? (int)$data['max_email_per_month'] : null);
            $plan->setHasApiAccess(isset($data['has_api_access']));
            $plan->setHasAdvancedReports(isset($data['has_advanced_reports']));
            $plan->setHasBarcodeScanner(isset($data['has_barcode_scanner']));
            $plan->setHasAiFeatures(isset($data['has_ai_features']));
            $plan->setHasWhiteLabel(isset($data['has_white_label']));
            $plan->setHasPrioritySupport(isset($data['has_priority_support']));
            $plan->setHasCustomDomain(isset($data['has_custom_domain']));
            $plan->setIsActive(isset($data['is_active']));
            $plan->setIsPopular(isset($data['is_popular']));
            $plan->setPriority((int)($data['priority'] ?? 0));

            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik paketi başarıyla güncellendi.');
            return $this->redirectToRoute('admin_plans');
        }

        return $this->render('admin/plan/edit.html.twig', [
            'plan' => $plan,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'admin_plans_toggle_active', methods: ['POST'])]
    public function toggleActive(SubscriptionPlan $plan): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $plan->setIsActive(!$plan->isActive());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'is_active' => $plan->isActive(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_plans_delete', methods: ['POST'])]
    public function delete(SubscriptionPlan $plan): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $this->entityManager->remove($plan);
        $this->entityManager->flush();

        $this->addFlash('success', 'Abonelik paketi başarıyla silindi.');
        return $this->redirectToRoute('admin_plans');
    }
}
