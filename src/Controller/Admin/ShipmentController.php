<?php

namespace App\Controller\Admin;

use App\Entity\Shipment;
use App\Entity\Order;
use App\Entity\CargoCompany;
use App\Repository\ShipmentRepository;
use App\Repository\OrderRepository;
use App\Repository\CargoCompanyRepository;
use App\Service\Shipment\ShipmentService;
use App\Service\Cargo\CargoApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/shipments')]
class ShipmentController extends AbstractController
{
    public function __construct(
        private ShipmentRepository $shipmentRepository,
        private OrderRepository $orderRepository,
        private CargoCompanyRepository $cargoCompanyRepository,
        private ShipmentService $shipmentService,
        private CargoApiService $cargoApiService
    ) {}

    #[Route('', name: 'admin_shipments', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $status = $request->query->get('status', 'all');
        $cargoCompanyId = $request->query->get('cargo_company', null);

        $qb = $this->shipmentRepository->createQueryBuilder('s')
            ->leftJoin('s.order', 'o')
            ->leftJoin('s.cargoCompany', 'cc')
            ->addSelect('o', 'cc');

        if ($status !== 'all') {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $status);
        }

        if ($cargoCompanyId) {
            $qb->andWhere('s.cargoCompany = :cargoCompany')
               ->setParameter('cargoCompany', $cargoCompanyId);
        }

        $qb->orderBy('s.createdAt', 'DESC')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        $shipments = $qb->getQuery()->getResult();

        // Count totals
        $countQb = $this->shipmentRepository->createQueryBuilder('s')
            ->select('count(s.id)');
        
        if ($cargoCompanyId) {
            $countQb->andWhere('s.cargoCompany = :cargoCompany')
                    ->setParameter('cargoCompany', $cargoCompanyId);
        }

        $total = $countQb->getQuery()->getSingleScalarResult();
        $pages = ceil($total / $limit);

        // Status counts
        $statusCounts = [
            'created' => $this->getStatusCount('created', $cargoCompanyId),
            'in_transit' => $this->getStatusCount('in_transit', $cargoCompanyId),
            'out_for_delivery' => $this->getStatusCount('out_for_delivery', $cargoCompanyId),
            'delivered' => $this->getStatusCount('delivered', $cargoCompanyId),
        ];

        $cargoCompanies = $this->cargoCompanyRepository->findBy(['isActive' => true]);

        return $this->render('admin/shipment/index.html.twig', [
            'shipments' => $shipments,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_status' => $status,
            'current_cargo_company' => $cargoCompanyId,
            'status_counts' => $statusCounts,
            'cargo_companies' => $cargoCompanies,
        ]);
    }

    private function getStatusCount(string $status, ?string $cargoCompanyId): int
    {
        $qb = $this->shipmentRepository->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', $status);

        if ($cargoCompanyId) {
            $qb->andWhere('s.cargoCompany = :cargoCompany')
               ->setParameter('cargoCompany', $cargoCompanyId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    #[Route('/create', name: 'admin_shipments_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $orderId = $request->request->get('order_id');
            $cargoCompanyId = $request->request->get('cargo_company_id');

            $order = $this->orderRepository->find($orderId);
            $cargoCompany = $this->cargoCompanyRepository->find($cargoCompanyId);

            if (!$order || !$cargoCompany) {
                $this->addFlash('error', 'Geçersiz sipariş veya kargo firması.');
                return $this->redirectToRoute('admin_shipments_create');
            }

            try {
                // Use ShipmentService to create shipment
                $shipmentData = [
                    'tracking_number' => $request->request->get('tracking_number'),
                    'service_type' => $request->request->get('service_type', 'standard'),
                    'package_count' => (int)$request->request->get('package_count', 1),
                    'weight' => $request->request->get('weight'),
                    'desi' => $request->request->get('desi'),
                    'estimated_cost' => $request->request->get('estimated_cost'),
                    'requires_signature' => $request->request->get('requires_signature') === '1',
                    'is_cod' => $request->request->get('is_cod') === '1',
                    'cod_amount' => $request->request->get('cod_amount'),
                    'notes' => $request->request->get('notes'),
                ];

                $shipment = $this->shipmentService->createShipment($order, $cargoCompany, $shipmentData);

                $this->addFlash('success', 'Gönderi başarıyla oluşturuldu.');
                return $this->redirectToRoute('admin_shipment_detail', ['id' => $shipment->getId()]);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('admin_shipments_create');
            }
        }

        // GET request
        $orders = $this->orderRepository->createQueryBuilder('o')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        $cargoCompanies = $this->cargoCompanyRepository->findBy(['isActive' => true]);

        return $this->render('admin/shipment/create.html.twig', [
            'orders' => $orders,
            'cargo_companies' => $cargoCompanies,
        ]);
    }

    #[Route('/{id}', name: 'admin_shipment_detail', methods: ['GET'])]
    public function detail(Shipment $shipment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('admin/shipment/detail.html.twig', [
            'shipment' => $shipment,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_shipments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shipment $shipment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $shipment->setStatus($request->request->get('status'));
            $shipment->setServiceType($request->request->get('service_type'));
            $shipment->setPackageCount((int)$request->request->get('package_count', 1));
            
            if ($request->request->get('weight')) {
                $shipment->setWeight($request->request->get('weight'));
            }
            if ($request->request->get('desi')) {
                $shipment->setDesi($request->request->get('desi'));
            }
            if ($request->request->get('estimated_cost')) {
                $shipment->setEstimatedCost($request->request->get('estimated_cost'));
            }
            if ($request->request->get('actual_cost')) {
                $shipment->setActualCost($request->request->get('actual_cost'));
            }

            $shipment->setRequiresSignature($request->request->get('requires_signature') === '1');
            $shipment->setIsCOD($request->request->get('is_cod') === '1');
            
            if ($shipment->getIsCOD() && $request->request->get('cod_amount')) {
                $shipment->setCodAmount($request->request->get('cod_amount'));
            }

            if ($request->request->get('notes')) {
                $shipment->setNotes($request->request->get('notes'));
            }

            if ($request->request->get('estimated_delivery_date')) {
                $shipment->setEstimatedDeliveryDate(new \DateTime($request->request->get('estimated_delivery_date')));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Gönderi bilgileri güncellendi.');
            return $this->redirectToRoute('admin_shipment_detail', ['id' => $shipment->getId()]);
        }

        return $this->render('admin/shipment/edit.html.twig', [
            'shipment' => $shipment,
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_shipments_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Shipment $shipment): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;
        $message = $data['message'] ?? null;

        if (!$newStatus) {
            return new JsonResponse(['success' => false, 'message' => 'Durum belirtilmedi'], 400);
        }

        try {
            // Use ShipmentService to update status (includes automatic order sync!)
            $this->shipmentService->updateStatus($shipment, $newStatus, $message);

            return new JsonResponse([
                'success' => true,
                'message' => 'Gönderi durumu güncellendi',
                'status' => $newStatus
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/track', name: 'admin_shipments_track', methods: ['POST'])]
    public function track(Shipment $shipment): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Use CargoApiService to track shipment
        $result = $this->cargoApiService->trackShipment($shipment);

        if (!$result['success']) {
            // Fallback to local tracking info
            return new JsonResponse([
                'success' => true,
                'message' => 'Yerel takip bilgisi gösteriliyor',
                'data' => [
                    'tracking_number' => $shipment->getTrackingNumber(),
                    'status' => $shipment->getStatus(),
                    'last_update' => $shipment->getLastTrackedAt()?->format('d.m.Y H:i'),
                    'history' => $shipment->getTrackingHistory(),
                    'tracking_url' => $result['tracking_url'] ?? null,
                ]
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Takip bilgisi güncellendi',
            'data' => $result
        ]);
    }

    #[Route('/bulk/update-status', name: 'admin_shipments_bulk_update', methods: ['POST'])]
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $data = json_decode($request->getContent(), true);
        $shipmentIds = $data['shipment_ids'] ?? [];
        $newStatus = $data['status'] ?? null;

        if (empty($shipmentIds) || !$newStatus) {
            return new JsonResponse(['success' => false, 'message' => 'Geçersiz parametreler'], 400);
        }

        $results = $this->shipmentService->bulkUpdateStatus($shipmentIds, $newStatus);

        return new JsonResponse([
            'success' => $results['success'] > 0,
            'message' => "{$results['success']} gönderi güncellendi",
            'updated' => $results['success'],
            'failed' => $results['failed'],
            'errors' => $results['errors'],
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_shipments_delete', methods: ['POST'])]
    public function delete(Shipment $shipment): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // Don't allow deletion of delivered shipments
        if (in_array($shipment->getStatus(), ['delivered', 'in_transit', 'out_for_delivery'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Teslim edilen veya yolda olan gönderi silinemez'
            ], 400);
        }

        $this->entityManager->remove($shipment);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Gönderi silindi'
        ]);
    }

}
