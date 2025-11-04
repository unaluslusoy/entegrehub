<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ShipmentRepository;
use App\Repository\CargoCompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reports')]
class ReportController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ShipmentRepository $shipmentRepository,
        private CargoCompanyRepository $cargoCompanyRepository
    ) {}

    #[Route('/', name: 'admin_reports', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('admin/reports/index.html.twig');
    }

    #[Route('/orders', name: 'admin_reports_orders', methods: ['GET'])]
    public function orders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        
        // Get date range from request
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : new \DateTime('first day of this month');
        
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : new \DateTime('last day of this month');

        // Get order statistics
        $totalOrders = $this->orderRepository->countByUserAndDateRange($user, $startDate, $endDate);
        $totalRevenue = $this->orderRepository->sumRevenueByUserAndDateRange($user, $startDate, $endDate);
        
        // Get orders by status
        $ordersByStatus = $this->orderRepository->getOrderCountByStatus($user);
        
        // Get monthly order stats
        $monthlyOrders = $this->orderRepository->getMonthlyOrderStats($user, 12);

        return $this->render('admin/reports/orders.html.twig', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'orders_by_status' => $ordersByStatus,
            'monthly_orders' => $monthlyOrders,
        ]);
    }

    #[Route('/shipments', name: 'admin_reports_shipments', methods: ['GET'])]
    public function shipments(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        
        // Get date range from request
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : new \DateTime('first day of this month');
        
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : new \DateTime('last day of this month');

        // Get shipment statistics
        $totalShipments = $this->shipmentRepository->countByUserAndDateRange($user, $startDate, $endDate);
        $totalCost = $this->shipmentRepository->sumCostByUserAndDateRange($user, $startDate, $endDate);
        
        // Get delivery success rate
        $deliveryStats = $this->shipmentRepository->getDeliverySuccessRate($user, $startDate, $endDate);
        
        // Get shipments by status
        $shipmentsByStatus = $this->shipmentRepository->getShipmentCountByStatus($user);
        
        // Get monthly shipment stats
        $monthlyShipments = $this->shipmentRepository->getMonthlyShipmentStats($user, 12);
        
        // Get cargo company performance
        $cargoPerformance = $this->shipmentRepository->getCargoCompanyPerformance($user, $startDate, $endDate);

        return $this->render('admin/reports/shipments.html.twig', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_shipments' => $totalShipments,
            'total_cost' => $totalCost,
            'delivery_stats' => $deliveryStats,
            'shipments_by_status' => $shipmentsByStatus,
            'monthly_shipments' => $monthlyShipments,
            'cargo_performance' => $cargoPerformance,
        ]);
    }

    #[Route('/cargo-performance', name: 'admin_reports_cargo_performance', methods: ['GET'])]
    public function cargoPerformance(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        
        // Get date range from request
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : new \DateTime('first day of this month');
        
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : new \DateTime('last day of this month');

        // Get cargo company performance
        $cargoPerformance = $this->shipmentRepository->getCargoCompanyPerformance($user, $startDate, $endDate);

        return $this->render('admin/reports/cargo_performance.html.twig', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cargo_performance' => $cargoPerformance,
        ]);
    }

    #[Route('/financial', name: 'admin_reports_financial', methods: ['GET'])]
    public function financial(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        
        // Get date range from request
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : new \DateTime('first day of this month');
        
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : new \DateTime('last day of this month');

        // Get financial statistics
        $totalRevenue = $this->orderRepository->sumRevenueByUserAndDateRange($user, $startDate, $endDate);
        $totalCargoCost = $this->shipmentRepository->sumCostByUserAndDateRange($user, $startDate, $endDate);
        
        // Get orders by status
        $ordersByStatus = $this->orderRepository->getOrderCountByStatus($user);

        return $this->render('admin/reports/financial.html.twig', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_revenue' => $totalRevenue,
            'total_cargo_cost' => $totalCargoCost,
            'net_revenue' => $totalRevenue - $totalCargoCost,
            'orders_by_status' => $ordersByStatus,
        ]);
    }

    #[Route('/export/orders', name: 'admin_reports_export_orders', methods: ['GET'])]
    public function exportOrders(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        $format = $request->query->get('format', 'csv'); // csv, excel, pdf
        
        // Get date range
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : new \DateTime('first day of this month');
        
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : new \DateTime('last day of this month');

        // Get orders (TODO: Create specific method for export data)
        $orders = $this->orderRepository->findRecentByUser($user, 1000);

        if ($format === 'csv') {
            return $this->exportToCsv($orders, 'orders_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv');
        }

        // TODO: Implement Excel and PDF export
        $this->addFlash('warning', 'Excel ve PDF export yakında eklenecek');
        return $this->redirectToRoute('admin_reports_orders');
    }

    #[Route('/export/shipments', name: 'admin_reports_export_shipments', methods: ['GET'])]
    public function exportShipments(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        $format = $request->query->get('format', 'csv');
        
        // Get date range
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : new \DateTime('first day of this month');
        
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : new \DateTime('last day of this month');

        // Get shipments
        $shipments = $this->shipmentRepository->findRecentByUser($user, 1000);

        if ($format === 'csv') {
            return $this->exportShipmentsToCsv($shipments, 'shipments_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv');
        }

        $this->addFlash('warning', 'Excel ve PDF export yakında eklenecek');
        return $this->redirectToRoute('admin_reports_shipments');
    }

    /**
     * Export orders to CSV
     */
    private function exportToCsv(array $orders, string $filename): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->setCallback(function() use ($orders) {
            $handle = fopen('php://output', 'w+');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($handle, [
                'Sipariş No',
                'Müşteri Adı',
                'Müşteri Email',
                'Telefon',
                'Toplam Tutar',
                'Ödeme Yöntemi',
                'Durum',
                'Sipariş Tarihi',
                'Oluşturma Tarihi'
            ], ';');
            
            // Data rows
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->getOrderNumber(),
                    $order->getCustomerName(),
                    $order->getCustomerEmail(),
                    $order->getCustomerPhone(),
                    number_format($order->getTotalAmount(), 2, ',', '.'),
                    $this->translatePaymentMethod($order->getPaymentMethod()),
                    $this->translateOrderStatus($order->getStatus()),
                    $order->getOrderDate()->format('d.m.Y H:i'),
                    $order->getCreatedAt()->format('d.m.Y H:i'),
                ], ';');
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * Export shipments to CSV
     */
    private function exportShipmentsToCsv(array $shipments, string $filename): StreamedResponse
    {
        $response = new StreamedResponse();
        $response->setCallback(function() use ($shipments) {
            $handle = fopen('php://output', 'w+');
            
            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($handle, [
                'Takip No',
                'Sipariş No',
                'Kargo Firması',
                'Müşteri',
                'Durum',
                'Maliyet',
                'Oluşturma Tarihi',
                'Teslim Tarihi'
            ], ';');
            
            // Data rows
            foreach ($shipments as $shipment) {
                fputcsv($handle, [
                    $shipment->getTrackingNumber(),
                    $shipment->getOrder()->getOrderNumber(),
                    $shipment->getCargoCompany()->getName(),
                    $shipment->getOrder()->getCustomerName(),
                    $this->translateShipmentStatus($shipment->getStatus()),
                    number_format($shipment->getActualCost() ?? $shipment->getEstimatedCost() ?? 0, 2, ',', '.'),
                    $shipment->getCreatedAt()->format('d.m.Y H:i'),
                    $shipment->getDeliveredAt()?->format('d.m.Y H:i') ?? '-',
                ], ';');
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function translatePaymentMethod(string $method): string
    {
        return match($method) {
            'cod_cash' => 'Kapıda Nakit',
            'cod_credit_card' => 'Kapıda Kredi Kartı',
            'online' => 'Online Ödeme',
            default => $method
        };
    }

    private function translateOrderStatus(string $status): string
    {
        return match($status) {
            'pending' => 'Beklemede',
            'processing' => 'İşleniyor',
            'ready_to_ship' => 'Kargoya Hazır',
            'shipped' => 'Kargoda',
            'delivered' => 'Teslim Edildi',
            'cancelled' => 'İptal Edildi',
            default => $status
        };
    }

    private function translateShipmentStatus(string $status): string
    {
        return match($status) {
            'created' => 'Oluşturuldu',
            'picked_up' => 'Toplandı',
            'in_transit' => 'Yolda',
            'out_for_delivery' => 'Dağıtımda',
            'delivered' => 'Teslim Edildi',
            'cancelled' => 'İptal Edildi',
            'returned' => 'İade Edildi',
            default => $status
        };
    }
}
