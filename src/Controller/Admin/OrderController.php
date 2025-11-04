<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ShopRepository $shopRepository
    ) {}

    #[Route('/', name: 'admin_orders', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        $page = $request->query->getInt('page', 1);
        $limit = 50;
        
        // Get user's shops
        $shops = $this->shopRepository->findByUser($user);
        
        if (empty($shops)) {
            return $this->render('admin/order/index.html.twig', [
                'orders' => [],
                'page' => $page,
                'pages' => 0,
                'total' => 0,
            ]);
        }

        $orders = $this->orderRepository->findByShops(
            $shops,
            $limit,
            ($page - 1) * $limit
        );
        
        $total = $this->orderRepository->countByShops($shops);
        $pages = ceil($total / $limit);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    #[Route('/{id}', name: 'admin_order_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $user = $this->getUser();
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Sipariş bulunamadı.');
        }

        // Check if user owns this order's shop
        if ($order->getShop()->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Bu siparişe erişim yetkiniz yok.');
        }

        return $this->render('admin/order/detail.html.twig', [
            'order' => $order,
        ]);
    }
}
