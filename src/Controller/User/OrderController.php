<?php

namespace App\Controller\User;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ShopRepository;
use App\Service\Order\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ShopRepository $shopRepository,
        private OrderService $orderService
    ) {}

    /**
     * Order list page
     */
    #[Route('', name: 'user_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get user's shops
        $shops = $this->shopRepository->findBy(['user' => $user]);
        
        // Get filters
        $filters = [
            'shop_id' => $request->query->get('shop_id'),
            'status' => $request->query->get('status'),
            'payment_status' => $request->query->get('payment_status'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'search' => $request->query->get('search'),
        ];

        // Get orders
        $orders = $this->orderRepository->findByUser($user, $filters);

        // Get statistics (using service)
        $stats = $this->orderService->getStatisticsByUser($user);

        return $this->render('user/order/index.html.twig', [
            'orders' => $orders,
            'shops' => $shops,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * AJAX endpoint for DataTables
     */
    #[Route('/datatable', name: 'user_orders_datatable', methods: ['GET'])]
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
            'shop_id' => $request->query->get('shop_id'),
            'status' => $request->query->get('status'),
            'payment_status' => $request->query->get('payment_status'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'search' => $searchValue,
        ];

        // Get orders
        $orders = $this->orderRepository->findByUser($user, $filters, $length, $start);
        $totalRecords = $this->orderRepository->countByUserFiltered($user, $filters);

        // Format data for DataTables
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'shop_name' => $order->getShop()->getShopName(),
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'total_amount' => number_format($order->getTotalAmount(), 2),
                'currency' => $order->getCurrency(),
                'status' => $order->getStatus(),
                'payment_status' => $order->getPaymentStatus(),
                'payment_method' => $order->getPaymentMethod(),
                'order_date' => $order->getOrderDate()?->format('Y-m-d H:i'),
                'created_at' => $order->getCreatedAt()->format('Y-m-d H:i'),
                'actions' => $this->renderView('user/order/_actions.html.twig', ['order' => $order]),
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
     * Order detail page
     */
    #[Route('/{id}', name: 'user_order_detail', methods: ['GET'])]
    public function detail(Order $order): Response
    {
        // Check ownership (using service)
        if (!$this->orderService->validateOwnership($order, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('user/order/detail.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Update order status (refactored to use OrderService)
     */
    #[Route('/{id}/status', name: 'user_order_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->validateOwnership($order, $this->getUser())) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $newStatus = $request->request->get('status');
        $note = $request->request->get('note');

        try {
            $this->orderService->updateStatus($order, $newStatus, $note);

            return new JsonResponse([
                'success' => true,
                'message' => 'Sipariş durumu güncellendi',
                'status' => $newStatus,
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Cancel order (refactored to use OrderService)
     */
    #[Route('/{id}/cancel', name: 'user_order_cancel', methods: ['POST'])]
    public function cancel(Request $request, Order $order): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->validateOwnership($order, $this->getUser())) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $reason = $request->request->get('reason', 'Kullanıcı tarafından iptal edildi');

        try {
            $this->orderService->cancelOrder($order, $reason);

            return new JsonResponse([
                'success' => true,
                'message' => 'Sipariş iptal edildi',
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Add internal note to order (refactored to use OrderService)
     */
    #[Route('/{id}/note', name: 'user_order_add_note', methods: ['POST'])]
    public function addNote(Request $request, Order $order): JsonResponse
    {
        // Check ownership
        if (!$this->orderService->validateOwnership($order, $this->getUser())) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $note = $request->request->get('note');
        if (empty($note)) {
            return new JsonResponse(['error' => 'Not boş olamaz'], Response::HTTP_BAD_REQUEST);
        }

        $this->orderService->addNote($order, $note);

        return new JsonResponse([
            'success' => true,
            'message' => 'Not eklendi',
        ]);
    }

    /**
     * Export orders to CSV
     */
    #[Route('/export/csv', name: 'user_orders_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get filters
        $filters = [
            'shop_id' => $request->query->get('shop_id'),
            'status' => $request->query->get('status'),
            'payment_status' => $request->query->get('payment_status'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
        ];

        // Get all orders (no limit for export)
        $orders = $this->orderRepository->findByUser($user, $filters, limit: 10000);

        // Create CSV content
        $output = fopen('php://temp', 'r+');
        
        // Header
        fputcsv($output, [
            'Sipariş No',
            'Mağaza',
            'Müşteri Adı',
            'Müşteri Email',
            'Müşteri Telefon',
            'Tutar',
            'Para Birimi',
            'Durum',
            'Ödeme Durumu',
            'Ödeme Yöntemi',
            'Sipariş Tarihi',
            'Oluşturulma Tarihi',
        ]);

        // Data rows
        foreach ($orders as $order) {
            fputcsv($output, [
                $order->getOrderNumber(),
                $order->getShop()->getShopName(),
                $order->getCustomerName(),
                $order->getCustomerEmail(),
                $order->getCustomerPhone(),
                $order->getTotalAmount(),
                $order->getCurrency(),
                $order->getStatus(),
                $order->getPaymentStatus(),
                $order->getPaymentMethod(),
                $order->getOrderDate()?->format('Y-m-d H:i:s'),
                $order->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="orders_' . date('Y-m-d_His') . '.csv"');

        return $response;
    }

    /**
     * Bulk status update (refactored to use OrderService)
     */
    #[Route('/bulk/status', name: 'user_orders_bulk_status', methods: ['POST'])]
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $orderIds = $request->request->all('order_ids');
        $newStatus = $request->request->get('status');

        if (empty($orderIds) || !$newStatus) {
            return new JsonResponse(['error' => 'Geçersiz parametreler'], Response::HTTP_BAD_REQUEST);
        }

        $results = $this->orderService->bulkUpdateStatus($orderIds, $newStatus, $user);

        return new JsonResponse([
            'success' => $results['success'] > 0,
            'message' => "{$results['success']} sipariş güncellendi",
            'updated' => $results['success'],
            'failed' => $results['failed'],
            'errors' => $results['errors'],
        ]);
    }

    /**
     * Get order statistics (refactored to use OrderService)
     */
    #[Route('/stats/dashboard', name: 'user_orders_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->orderService->getStatisticsByUser($user);

        return new JsonResponse($stats);
    }
}
