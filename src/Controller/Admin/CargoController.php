<?php

namespace App\Controller\Admin;

use App\Entity\CargoCompany;
use App\Repository\CargoCompanyRepository;
use App\Repository\ShipmentRepository;
use App\Service\Cargo\CargoApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/cargo-companies')]
class CargoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CargoCompanyRepository $cargoCompanyRepository,
        private ShipmentRepository $shipmentRepository,
        private CargoApiService $cargoApiService
    ) {}

    #[Route('', name: 'admin_cargo_companies', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $companies = $this->cargoCompanyRepository->findBy([], ['priority' => 'DESC', 'name' => 'ASC']);

        // Calculate statistics for each company
        $stats = [];
        foreach ($companies as $company) {
            $stats[$company->getId()] = [
                'total_shipments' => count($company->getShipments()),
                'active_shipments' => $this->shipmentRepository->createQueryBuilder('s')
                    ->select('count(s.id)')
                    ->where('s.cargoCompany = :company')
                    ->andWhere('s.status NOT IN (:statuses)')
                    ->setParameter('company', $company)
                    ->setParameter('statuses', ['delivered', 'cancelled', 'returned'])
                    ->getQuery()
                    ->getSingleScalarResult(),
            ];
        }

        return $this->render('admin/cargo/index.html.twig', [
            'companies' => $companies,
            'stats' => $stats,
        ]);
    }

    #[Route('/create', name: 'admin_cargo_companies_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $company = new CargoCompany();
            $company->setCode($request->request->get('code'));
            $company->setName($request->request->get('name'));
            $company->setLogo($request->request->get('logo'));
            $company->setApiUrl($request->request->get('api_url'));
            $company->setTrackingUrl($request->request->get('tracking_url'));
            $company->setPriority((int)$request->request->get('priority', 0));
            
            if ($request->request->get('base_cost')) {
                $company->setBaseCost($request->request->get('base_cost'));
            }
            if ($request->request->get('cost_per_kg')) {
                $company->setCostPerKg($request->request->get('cost_per_kg'));
            }

            $company->setIsActive($request->request->get('is_active') === '1');

            // Handle credentials
            $credentials = [];
            if ($request->request->get('api_key')) {
                $credentials['api_key'] = $request->request->get('api_key');
            }
            if ($request->request->get('api_secret')) {
                $credentials['api_secret'] = $request->request->get('api_secret');
            }
            if ($request->request->get('username')) {
                $credentials['username'] = $request->request->get('username');
            }
            if ($request->request->get('password')) {
                $credentials['password'] = $request->request->get('password');
            }
            if (!empty($credentials)) {
                $company->setCredentials($credentials);
            }

            // Handle settings
            $settings = [];
            if ($request->request->get('service_types')) {
                $settings['service_types'] = array_filter(explode(',', $request->request->get('service_types')));
            }
            if ($request->request->get('max_weight')) {
                $settings['max_weight'] = (float)$request->request->get('max_weight');
            }
            if ($request->request->get('timeout')) {
                $settings['timeout'] = (int)$request->request->get('timeout');
            }
            if ($request->request->get('test_mode')) {
                $settings['test_mode'] = $request->request->get('test_mode') === '1';
            }
            if (!empty($settings)) {
                $company->setSettings($settings);
            }

            if ($request->request->get('notes')) {
                $company->setNotes($request->request->get('notes'));
            }

            $this->entityManager->persist($company);
            $this->entityManager->flush();

            $this->addFlash('success', 'Kargo firması başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_cargo_company_detail', ['id' => $company->getId()]);
        }

        return $this->render('admin/cargo/create.html.twig');
    }

    #[Route('/{id}', name: 'admin_cargo_company_detail', methods: ['GET'])]
    public function detail(CargoCompany $company): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Get shipment statistics
        $stats = [
            'total' => count($company->getShipments()),
            'created' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];

        $statusCounts = $this->shipmentRepository->createQueryBuilder('s')
            ->select('s.status, count(s.id) as cnt')
            ->where('s.cargoCompany = :company')
            ->setParameter('company', $company)
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        foreach ($statusCounts as $row) {
            if (isset($stats[$row['status']])) {
                $stats[$row['status']] = $row['cnt'];
            }
        }

        // Get recent shipments
        $recentShipments = $this->shipmentRepository->createQueryBuilder('s')
            ->where('s.cargoCompany = :company')
            ->setParameter('company', $company)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin/cargo/detail.html.twig', [
            'company' => $company,
            'stats' => $stats,
            'recent_shipments' => $recentShipments,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_cargo_companies_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CargoCompany $company): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $company->setCode($request->request->get('code'));
            $company->setName($request->request->get('name'));
            $company->setLogo($request->request->get('logo'));
            $company->setApiUrl($request->request->get('api_url'));
            $company->setTrackingUrl($request->request->get('tracking_url'));
            $company->setPriority((int)$request->request->get('priority', 0));
            
            if ($request->request->get('base_cost')) {
                $company->setBaseCost($request->request->get('base_cost'));
            }
            if ($request->request->get('cost_per_kg')) {
                $company->setCostPerKg($request->request->get('cost_per_kg'));
            }

            $company->setIsActive($request->request->get('is_active') === '1');

            // Handle credentials - only update if provided
            $existingCredentials = $company->getCredentials() ?? [];
            if ($request->request->get('api_key')) {
                $existingCredentials['api_key'] = $request->request->get('api_key');
            }
            if ($request->request->get('api_secret')) {
                $existingCredentials['api_secret'] = $request->request->get('api_secret');
            }
            if ($request->request->get('username')) {
                $existingCredentials['username'] = $request->request->get('username');
            }
            if ($request->request->get('password')) {
                $existingCredentials['password'] = $request->request->get('password');
            }
            $company->setCredentials($existingCredentials);

            // Handle settings
            $existingSettings = $company->getSettings() ?? [];
            if ($request->request->get('service_types')) {
                $existingSettings['service_types'] = array_filter(explode(',', $request->request->get('service_types')));
            }
            if ($request->request->get('max_weight') !== null) {
                $existingSettings['max_weight'] = (float)$request->request->get('max_weight');
            }
            if ($request->request->get('timeout') !== null) {
                $existingSettings['timeout'] = (int)$request->request->get('timeout');
            }
            $existingSettings['test_mode'] = $request->request->get('test_mode') === '1';
            $company->setSettings($existingSettings);

            if ($request->request->get('notes') !== null) {
                $company->setNotes($request->request->get('notes'));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Kargo firması bilgileri güncellendi.');
            return $this->redirectToRoute('admin_cargo_company_detail', ['id' => $company->getId()]);
        }

        return $this->render('admin/cargo/edit.html.twig', [
            'company' => $company,
        ]);
    }

    #[Route('/{id}/toggle', name: 'admin_cargo_companies_toggle', methods: ['POST'])]
    public function toggle(CargoCompany $company): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $company->setIsActive(!$company->isActive());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $company->isActive() ? 'Kargo firması aktif edildi' : 'Kargo firması pasif edildi',
            'is_active' => $company->isActive()
        ]);
    }

    #[Route('/{id}/test-connection', name: 'admin_cargo_companies_test', methods: ['POST'])]
    public function testConnection(CargoCompany $company): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if (!$company->getApiUrl()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'API URL tanımlanmamış'
            ], 400);
        }

        if (!$company->getCredentials()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'API kimlik bilgileri tanımlanmamış'
            ], 400);
        }

        // Use CargoApiService to test connection
        $result = $this->cargoApiService->testConnection($company);

        // Add test mode info if available
        $settings = $company->getSettings() ?? [];
        if (isset($settings['test_mode']) && $settings['test_mode']) {
            $result['test_mode'] = true;
            if ($result['success']) {
                $result['message'] .= ' (Test Modu)';
            }
        }

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    #[Route('/{id}/delete', name: 'admin_cargo_companies_delete', methods: ['POST'])]
    public function delete(CargoCompany $company): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Check if company has shipments
        if (count($company->getShipments()) > 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Bu kargo firmasına ait gönderiler bulunmaktadır. Önce gönderiLERİ silmelisiniz.'
            ], 400);
        }

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Kargo firması silindi'
        ]);
    }
}
