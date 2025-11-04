<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ShopRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/shops')]
class ShopController extends AbstractController
{
    public function __construct(
        private ShopRepository $shopRepository,
        private OrderRepository $orderRepository,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'admin_shops', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $user = $this->getUser();
        $shops = $this->shopRepository->findByUser($user);

        return $this->render('admin/shop/index.html.twig', [
            'shops' => $shops,
        ]);
    }

    #[Route('/{id}', name: 'admin_shop_detail', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $shop = $this->shopRepository->find($id);

        if (!$shop || $shop->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Mağaza bulunamadı.');
        }

        // Get shop statistics
        $orderCount = $this->orderRepository->countByShop($shop, []);
        $pendingCount = $this->orderRepository->countByShop($shop, ['status' => 'pending']);
        
        // Get recent orders
        $recentOrders = $this->orderRepository->findByShop($shop, [], 10);

        // Calculate today's stats
        $today = new \DateTime('today');
        $tomorrow = (clone $today)->modify('+1 day');
        $todayOrders = $this->orderRepository->countByShop($shop, [
            'date_from' => $today,
            'date_to' => $tomorrow,
        ]);

        return $this->render('admin/shop/detail.html.twig', [
            'shop' => $shop,
            'stats' => [
                'total_orders' => $orderCount,
                'pending_orders' => $pendingCount,
                'today_orders' => $todayOrders,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'admin_shop_toggle_active', methods: ['POST'])]
    public function toggleActive(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $shop = $this->shopRepository->find($id);

        if (!$shop || $shop->getUser() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Mağaza bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $shop->setIsActive(!$shop->isActive());
            $this->shopRepository->save($shop, true);

            return $this->json([
                'success' => true,
                'is_active' => $shop->isActive(),
                'message' => $shop->isActive() ? 'Mağaza aktif edildi.' : 'Mağaza pasif edildi.',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to toggle shop active status', [
                'shop_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'İşlem başarısız oldu.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/toggle-auto-sync', name: 'admin_shop_toggle_auto_sync', methods: ['POST'])]
    public function toggleAutoSync(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $shop = $this->shopRepository->find($id);

        if (!$shop || $shop->getUser() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Mağaza bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $shop->setAutoSync(!$shop->isAutoSync());
            $this->shopRepository->save($shop, true);

            return $this->json([
                'success' => true,
                'auto_sync' => $shop->isAutoSync(),
                'message' => $shop->isAutoSync() ? 'Otomatik senkronizasyon açıldı.' : 'Otomatik senkronizasyon kapatıldı.',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to toggle shop auto sync', [
                'shop_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'İşlem başarısız oldu.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/sync', name: 'admin_shop_sync', methods: ['POST'])]
    public function sync(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $shop = $this->shopRepository->find($id);

        if (!$shop || $shop->getUser() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Mağaza bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$shop->isActive()) {
            return $this->json([
                'success' => false,
                'error' => 'Mağaza aktif değil.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->syncService->syncNewOrders($shop);
            
            $this->logger->info('Manual shop sync completed', [
                'shop_id' => $id,
                'result' => $result,
            ]);

            return $this->json([
                'success' => true,
                'synced' => $result['synced'] ?? 0,
                'total' => $result['total'] ?? 0,
                'errors' => $result['errors'] ?? [],
                'message' => sprintf('%d sipariş senkronize edildi.', $result['synced'] ?? 0),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Manual shop sync failed', [
                'shop_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Senkronizasyon başarısız oldu: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/verify', name: 'admin_shop_verify', methods: ['POST'])]
    public function verify(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $shop = $this->shopRepository->find($id);

        if (!$shop || $shop->getUser() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Mağaza bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $isValid = $this->oauthService->verifyShop($shop);
            
            if ($isValid) {
                return $this->json([
                    'success' => true,
                    'is_valid' => true,
                    'message' => 'Mağaza bağlantısı geçerli.',
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'is_valid' => false,
                    'error' => 'Mağaza bağlantısı geçersiz. Lütfen yeniden bağlayın.',
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Shop verification failed', [
                'shop_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Doğrulama başarısız oldu.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/delete', name: 'admin_shop_delete', methods: ['POST'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $shop = $this->shopRepository->find($id);

        if (!$shop || $shop->getUser() !== $this->getUser()) {
            return $this->json([
                'success' => false,
                'error' => 'Mağaza bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->oauthService->handleUninstall($shop);
            
            $this->logger->info('Shop deleted by user', [
                'shop_id' => $id,
                'shop_domain' => $shop->getShopDomain(),
                'user_id' => $this->getUser()->getId(),
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Mağaza başarıyla kaldırıldı.',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Shop deletion failed', [
                'shop_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Mağaza kaldırma başarısız oldu.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
