<?php

namespace App\Controller\User;

use App\Repository\OrderRepository;
use App\Repository\ShipmentRepository;
use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ShopRepository $shopRepository,
        private ShipmentRepository $shipmentRepository
    ) {}

    #[Route('/', name: 'user_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        // Get statistics
        $orderStats = $this->orderRepository->getStatisticsByUser($user);
        $shipmentStats = $this->shipmentRepository->getStatisticsByUser($user);

        // Get recent orders
        $recentOrders = $this->orderRepository->findByUser($user, [], 5, 0);

        // Get recent shipments  
        $recentShipments = $this->shipmentRepository->findByFilters($user, [], 5, 0);

        // Get shops
        $shops = $this->shopRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        // Get monthly order stats for chart
        $monthlyOrders = $this->orderRepository->getMonthlyOrderStats($user, 6);

        return $this->render('user/dashboard/index.html.twig', [
            'order_stats' => $orderStats,
            'shipment_stats' => $shipmentStats,
            'recent_orders' => $recentOrders,
            'recent_shipments' => $recentShipments,
            'shops' => $shops,
            'monthly_orders' => $monthlyOrders,
        ]);
    }
}
