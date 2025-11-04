<?php

namespace App\Controller\User;

use App\Entity\Shipment;
use App\Entity\Order;
use App\Repository\ShipmentRepository;
use App\Repository\OrderRepository;
use App\Repository\CargoCompanyRepository;
use App\Repository\UserLabelTemplateRepository;
use App\Service\Cargo\CargoLabelGenerator;
use App\Service\Shipment\ShipmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/shipments')]
#[IsGranted('ROLE_USER')]
class ShipmentController extends AbstractController
{
    public function __construct(
        private ShipmentRepository $shipmentRepository,
        private OrderRepository $orderRepository,
        private CargoCompanyRepository $cargoCompanyRepository,
        private UserLabelTemplateRepository $templateRepository,
        private CargoLabelGenerator $labelGenerator,
        private ShipmentService $shipmentService
    ) {}

    /**
     * Shipment list page
     */
    #[Route('', name: 'user_shipments', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get filters
        $filters = [
            'status' => $request->query->get('status'),
            'cargo_company' => $request->query->get('cargo_company'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
        ];

        // Get shipments for display (first page)
        $shipments = $this->shipmentRepository->findByFilters($user, $filters, 25, 0);
        
        // Get cargo companies for filter
        $cargoCompanies = $this->cargoCompanyRepository->findBy(['isActive' => true]);

        // Get user's label templates
        $labelTemplates = $this->templateRepository->findActiveByUser($user);

        // Get statistics (using service)
        $stats = $this->shipmentService->getStatisticsByUser($user);

        return $this->render('user/shipment/index.html.twig', [
            'shipments' => $shipments,
            'cargo_companies' => $cargoCompanies,
            'label_templates' => $labelTemplates,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * AJAX endpoint for DataTables
     */
    #[Route('/datatable', name: 'user_shipments_datatable', methods: ['GET'])]
    public function datatable(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // DataTables parameters
        $draw = $request->query->getInt('draw', 1);
        $start = $request->query->getInt('start', 0);
        $length = $request->query->getInt('length', 10);
        $searchValue = $request->query->get('search')['value'] ?? '';
        
        // Filters
        $filters = [
            'status' => $request->query->get('status'),
            'cargo_company' => $request->query->get('cargo_company'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'search' => $searchValue,
        ];

        // Get shipments
        $shipments = $this->shipmentRepository->findByFilters($user, $filters, $length, $start);
        $totalRecords = $this->shipmentRepository->countByFilters($user, $filters);

        // Format data for DataTables
        $data = [];
        foreach ($shipments as $shipment) {
            $data[] = [
                'id' => $shipment->getId(),
                'tracking_number' => $shipment->getTrackingNumber(),
                'order_number' => $shipment->getOrder()->getOrderNumber(),
                'cargo_company' => $shipment->getCargoCompany()->getName(),
                'status' => $shipment->getStatus(),
                'service_type' => $shipment->getServiceType(),
                'weight' => $shipment->getWeight(),
                'is_cod' => $shipment->isIsCOD(),
                'cod_amount' => $shipment->getCodAmount(),
                'estimated_delivery' => $shipment->getEstimatedDeliveryDate()?->format('Y-m-d'),
                'created_at' => $shipment->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Shipment detail page
     */
    #[Route('/{id}', name: 'user_shipment_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $user = $this->getUser();
        $shipment = $this->shipmentRepository->find($id);

        // Check ownership (using service)
        if (!$shipment || !$this->shipmentService->validateOwnership($shipment, $user)) {
            throw $this->createAccessDeniedException('Bu gönderiye erişim yetkiniz yok.');
        }

        return $this->render('user/shipment/detail.html.twig', [
            'shipment' => $shipment,
        ]);
    }

    /**
     * Create shipment page
     */
    #[Route('/create', name: 'user_shipment_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($request->isMethod('POST')) {
            try {
                $orderId = $request->request->getInt('order_id');
                $cargoCompanyId = $request->request->getInt('cargo_company_id');
                
                $order = $this->orderRepository->find($orderId);
                if (!$order || $order->getShop()->getUser() !== $user) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Sipariş bulunamadı veya erişim yetkiniz yok.'
                    ], 403);
                }

                $cargoCompany = $this->cargoCompanyRepository->find($cargoCompanyId);
                if (!$cargoCompany || !$cargoCompany->isIsActive()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Kargo firması bulunamadı veya aktif değil.'
                    ], 400);
                }

                $shipment = new Shipment();
                $shipment->setOrder($order);
                $shipment->setCargoCompany($cargoCompany);
                $shipment->setTrackingNumber($this->generateTrackingNumber());
                $shipment->setServiceType($request->request->get('service_type', 'standard'));
                $shipment->setWeight($request->request->get('weight'));
                $shipment->setDesi($request->request->get('desi'));
                $shipment->setPackageCount($request->request->getInt('package_count', 1));
                $shipment->setRequiresSignature($request->request->getBoolean('requires_signature'));
                $shipment->setIsCOD($request->request->getBoolean('is_cod'));
                
                if ($shipment->isIsCOD()) {
                    $shipment->setCodAmount($order->getTotalAmount());
                }
                
                $shipment->setNotes($request->request->get('notes'));

                $this->entityManager->persist($shipment);
                
                // Update order status
                if ($order->getStatus() === 'processing') {
                    $order->setStatus('ready_to_ship');
                }
                
                $this->entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Gönderi başarıyla oluşturuldu.',
                    'shipment_id' => $shipment->getId()
                ]);

            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Gönderi oluşturulurken hata: ' . $e->getMessage()
                ], 500);
            }
        }

        // GET request - show form
        $orderId = $request->query->getInt('order_id');
        $order = null;
        
        if ($orderId) {
            $order = $this->orderRepository->find($orderId);
            if (!$order || $order->getShop()->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        $cargoCompanies = $this->cargoCompanyRepository->findBy(['isActive' => true]);
        
        // Get orders ready to ship
        $readyOrders = $this->orderRepository->findByUser($user, [
            'status' => 'processing'
        ], 100, 0);

        return $this->render('user/shipment/create.html.twig', [
            'order' => $order,
            'orders' => $readyOrders,
            'cargo_companies' => $cargoCompanies,
        ]);
    }

    /**
     * Update shipment status (refactored to use ShipmentService)
     */
    #[Route('/{id}/status', name: 'user_shipment_update_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $shipment = $this->shipmentRepository->find($id);

        if (!$shipment || !$this->shipmentService->validateOwnership($shipment, $user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gönderi bulunamadı.'
            ], 404);
        }

        $newStatus = $request->request->get('status');
        $note = $request->request->get('note');

        try {
            $this->shipmentService->updateStatus($shipment, $newStatus, $note);

            return new JsonResponse([
                'success' => true,
                'message' => 'Gönderi durumu güncellendi.'
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel shipment (refactored to use ShipmentService)
     */
    #[Route('/{id}/cancel', name: 'user_shipment_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $shipment = $this->shipmentRepository->find($id);

        if (!$shipment || !$this->shipmentService->validateOwnership($shipment, $user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gönderi bulunamadı.'
            ], 404);
        }

        $reason = $request->request->get('cancel_reason', 'Kullanıcı tarafından iptal edildi');

        try {
            $this->shipmentService->cancelShipment($shipment, $reason);

            return new JsonResponse([
                'success' => true,
                'message' => 'Gönderi iptal edildi.'
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Track shipment - manual refresh
     */
    #[Route('/{id}/track', name: 'user_shipment_track', methods: ['POST'])]
    public function track(int $id): JsonResponse
    {
        $user = $this->getUser();
        $shipment = $this->shipmentRepository->find($id);

        if (!$shipment || $shipment->getOrder()->getShop()->getUser() !== $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gönderi bulunamadı.'
            ], 404);
        }

        // TODO: Implement actual cargo tracking API call
        // For now, just update last tracked time
        $shipment->setLastTrackedAt(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Kargo takip bilgileri güncellendi.',
            'tracking_history' => $shipment->getTrackingHistory() ?? []
        ]);
    }

    /**
     * Print shipping label
     */
    #[Route('/{id}/label', name: 'user_shipment_print_label', methods: ['GET'])]
    public function printLabel(int $id, Request $request): Response
    {
        $user = $this->getUser();
        $shipment = $this->shipmentRepository->find($id);

        if (!$shipment || $shipment->getOrder()->getShop()->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Get template preference (can be template ID or 'default')
        $templateParam = $request->query->get('template', 'default');

        // Convert to integer if numeric, otherwise keep as string
        $template = is_numeric($templateParam) ? (int)$templateParam : $templateParam;

        // Generate and return PDF
        try {
            return $this->labelGenerator->generateLabel($shipment, $template);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Etiket oluşturulurken hata: ' . $e->getMessage());
            return $this->redirectToRoute('user_shipment_detail', ['id' => $id]);
        }
    }

    /**
     * Bulk print labels
     */
    #[Route('/labels/bulk-print', name: 'user_shipments_bulk_print', methods: ['POST', 'GET'])]
    public function bulkPrintLabels(Request $request): Response
    {
        $user = $this->getUser();

        // Get shipment IDs from POST or GET
        $shipmentIds = $request->isMethod('POST')
            ? $request->request->all('shipment_ids')
            : explode(',', $request->query->get('ids', ''));

        if (empty($shipmentIds)) {
            if ($request->isMethod('POST')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Gönderi seçilmedi.'
                ], 400);
            }
            $this->addFlash('error', 'Gönderi seçilmedi.');
            return $this->redirectToRoute('user_shipments');
        }

        // Fetch shipments and validate ownership
        $shipments = $this->shipmentRepository->findBy(['id' => $shipmentIds]);

        // Filter by user ownership
        $shipments = array_filter($shipments, function($shipment) use ($user) {
            return $shipment->getOrder()->getShop()->getUser() === $user;
        });

        if (empty($shipments)) {
            if ($request->isMethod('POST')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erişim yetkiniz olmayan gönderiler.'
                ], 403);
            }
            $this->addFlash('error', 'Erişim yetkiniz olmayan gönderiler.');
            return $this->redirectToRoute('user_shipments');
        }

        // Get template preference (can be template ID or 'default')
        $templateParam = $request->get('template', 'default');

        // Convert to integer if numeric, otherwise keep as string
        $template = is_numeric($templateParam) ? (int)$templateParam : $templateParam;

        // Generate bulk PDF
        try {
            return $this->labelGenerator->generateBulkLabels($shipments, $template);
        } catch (\Exception $e) {
            if ($request->isMethod('POST')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Etiketler oluşturulurken hata: ' . $e->getMessage()
                ], 500);
            }
            $this->addFlash('error', 'Etiketler oluşturulurken hata: ' . $e->getMessage());
            return $this->redirectToRoute('user_shipments');
        }
    }

    /**
     * Get shipment statistics for dashboard
     */
    #[Route('/stats/dashboard', name: 'user_shipments_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->shipmentRepository->getStatisticsByUser($user);

        return new JsonResponse($stats);
    }

    /**
     * Generate unique tracking number
     */
    private function generateTrackingNumber(): string
    {
        do {
            $trackingNumber = 'TRK' . strtoupper(substr(uniqid(), -10));
            $existing = $this->shipmentRepository->findByTrackingNumber($trackingNumber);
        } while ($existing);

        return $trackingNumber;
    }
}
