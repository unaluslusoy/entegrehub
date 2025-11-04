<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ShipmentRepository;
use App\Repository\ShopRepository;
use App\Repository\UserSubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ShopRepository $shopRepository,
        private ShipmentRepository $shipmentRepository,
        private UserSubscriptionRepository $subscriptionRepository
    ) {}

    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $user = $this->getUser();

        // Get statistics
        $stats = $this->getStatistics($user);

        // Get recent orders
        $recentOrders = $this->orderRepository->findRecentByUser($user, 10);

        // Get recent shipments
        $recentShipments = $this->shipmentRepository->findRecentByUser($user, 10);

        // Get active shops
        $activeShops = $this->shopRepository->findBy([
            'user' => $user,
            'isActive' => true,
        ], ['createdAt' => 'DESC'], 5);

        // Get subscription info
        $subscription = $this->subscriptionRepository->findActiveByUser($user);

        // Get monthly order chart data
        $monthlyOrders = $this->orderRepository->getMonthlyOrderStats($user, 6);

        // Get order status distribution
        $ordersByStatus = $this->orderRepository->getOrderCountByStatus($user);

        // Get shipment status distribution
        $shipmentsByStatus = $this->shipmentRepository->getShipmentCountByStatus($user);

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'recent_orders' => $recentOrders,
            'recent_shipments' => $recentShipments,
            'active_shops' => $activeShops,
            'subscription' => $subscription,
            'monthly_orders' => $monthlyOrders,
            'orders_by_status' => $ordersByStatus,
            'shipments_by_status' => $shipmentsByStatus,
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getStatistics($user): array
    {
        $now = new \DateTime();
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $startOfLastMonth = new \DateTime('first day of last month 00:00:00');
        $endOfLastMonth = new \DateTime('last day of last month 23:59:59');
        $startOfToday = new \DateTime('today 00:00:00');

        // Total orders
        $totalOrders = $this->orderRepository->countByUser($user);
        
        // Orders this month
        $ordersThisMonth = $this->orderRepository->countByUserAndDateRange(
            $user,
            $startOfMonth,
            $now
        );

        // Orders today
        $ordersToday = $this->orderRepository->countByUserAndDateRange(
            $user,
            $startOfToday,
            $now
        );

        // Orders last month
        $ordersLastMonth = $this->orderRepository->countByUserAndDateRange(
            $user,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // Calculate order growth
        $orderGrowth = $ordersLastMonth > 0 
            ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100, 1)
            : 0;

        // Total revenue this month
        $revenueThisMonth = $this->orderRepository->sumRevenueByUserAndDateRange(
            $user,
            $startOfMonth,
            $now
        );

        // Revenue last month
        $revenueLastMonth = $this->orderRepository->sumRevenueByUserAndDateRange(
            $user,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // Calculate revenue growth
        $revenueGrowth = $revenueLastMonth > 0 
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : 0;

        // Pending orders
        $pendingOrders = $this->orderRepository->countByUserAndStatus($user, 'pending');

        // Processing orders
        $processingOrders = $this->orderRepository->countByUserAndStatus($user, 'processing');

        // Total shipments
        $totalShipments = $this->shipmentRepository->countByUser($user);

        // Shipments this month
        $shipmentsThisMonth = $this->shipmentRepository->countByUserAndDateRange(
            $user,
            $startOfMonth,
            $now
        );

        // Active shipments (in_transit + out_for_delivery)
        $activeShipments = $this->shipmentRepository->countByUserAndStatuses(
            $user,
            ['created', 'picked_up', 'in_transit', 'out_for_delivery']
        );

        // Delivered this month
        $deliveredThisMonth = $this->shipmentRepository->countByUserAndDateRange(
            $user,
            $startOfMonth,
            $now
        );

        // Cargo cost this month
        $cargoCostThisMonth = $this->shipmentRepository->sumCostByUserAndDateRange(
            $user,
            $startOfMonth,
            $now
        );

        // Cargo cost last month
        $cargoCostLastMonth = $this->shipmentRepository->sumCostByUserAndDateRange(
            $user,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // Calculate cargo cost growth
        $cargoCostGrowth = $cargoCostLastMonth > 0 
            ? round((($cargoCostThisMonth - $cargoCostLastMonth) / $cargoCostLastMonth) * 100, 1)
            : 0;

        // Total shops
        $totalShops = $this->shopRepository->countByUser($user);

        // Active shops
        $activeShops = $this->shopRepository->countByUserAndActive($user, true);

        // Delivery success rate (this month)
        $deliveryStats = $this->shipmentRepository->getDeliverySuccessRate($user, $startOfMonth, $now);
        $deliverySuccessRate = $deliveryStats['total'] > 0 
            ? round(($deliveryStats['delivered'] / $deliveryStats['total']) * 100, 1)
            : 0;

        return [
            'total_orders' => $totalOrders,
            'orders_this_month' => $ordersThisMonth,
            'orders_today' => $ordersToday,
            'order_growth' => $orderGrowth,
            'revenue_this_month' => $revenueThisMonth,
            'revenue_last_month' => $revenueLastMonth,
            'revenue_growth' => $revenueGrowth,
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'total_shipments' => $totalShipments,
            'shipments_this_month' => $shipmentsThisMonth,
            'active_shipments' => $activeShipments,
            'delivered_this_month' => $deliveredThisMonth,
            'cargo_cost_this_month' => $cargoCostThisMonth,
            'cargo_cost_growth' => $cargoCostGrowth,
            'total_shops' => $totalShops,
            'active_shops' => $activeShops,
            'delivery_success_rate' => $deliverySuccessRate,
            'delivery_stats' => $deliveryStats,
        ];
    }

    #[Route('/quick-stats', name: 'admin_quick_stats', methods: ['GET'])]
    public function quickStats(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $user = $this->getUser();
        $stats = $this->getStatistics($user);

        return $this->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
