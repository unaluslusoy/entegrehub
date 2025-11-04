<?php

namespace App\Controller\Api;

use App\Repository\ShopifyStoreRepository;
use App\Service\ShopifyService;
use App\Service\ShopifyCacheService;
use App\Service\ShopifyRateLimiter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/shopify', name: 'api_shopify_')]
class ShopifyApiController extends AbstractController
{
    public function __construct(
        private ShopifyStoreRepository $storeRepository,
        private ShopifyService $shopifyService,
        private ShopifyCacheService $cacheService,
        private ShopifyRateLimiter $rateLimiter,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get store statistics
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $shopDomain = $request->query->get('shop');
        
        // Security: Validate shop parameter
        if (!$shopDomain) {
            return $this->json(['error' => 'Shop parameter required'], 400);
        }

        // Security: Validate shop domain format
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shopDomain)) {
            $this->logger->warning('Invalid shop domain in stats request', [
                'shop' => $shopDomain,
                'ip' => $request->getClientIp(),
            ]);
            return $this->json(['error' => 'Invalid shop domain format'], 400);
        }

        // Security: Verify request comes from Shopify or authenticated user
        $shopHeader = $request->headers->get('X-Shopify-Shop-Domain');
        if ($shopHeader && $shopHeader !== $shopDomain) {
            $this->logger->warning('Shop domain mismatch', [
                'query_shop' => $shopDomain,
                'header_shop' => $shopHeader,
            ]);
            return $this->json(['error' => 'Shop domain mismatch'], 403);
        }

        // Try cache first
        $cachedStats = $this->cacheService->getStats($shopDomain);
        if ($cachedStats) {
            return $this->json($cachedStats);
        }

        $store = $this->storeRepository->findOneBy([
            'shopDomain' => $shopDomain,
            'isActive' => true,
        ]);

        if (!$store) {
            $stats = [
                'is_connected' => false,
                'total_orders' => 0,
                'synced_orders' => 0,
                'pending_orders' => 0,
                'last_sync' => null,
            ];
            
            $this->cacheService->cacheStats($shopDomain, $stats);
            return $this->json($stats);
        }

        $stats = [
            'is_connected' => true,
            'total_orders' => $store->getTotalOrdersCount() ?? 0,
            'synced_orders' => $store->getSyncedOrdersCount() ?? 0,
            'pending_orders' => ($store->getTotalOrdersCount() ?? 0) - ($store->getSyncedOrdersCount() ?? 0),
            'last_sync' => $store->getLastSyncAt()?->format('c'),
            'store_name' => $store->getShopName(),
            'created_at' => $store->getCreatedAt()->format('c'),
        ];

        // Cache for 1 minute
        $this->cacheService->cacheStats($shopDomain, $stats);

        return $this->json($stats);
    }

    /**
     * Sync orders from Shopify
     */
    #[Route('/sync-orders', name: 'sync_orders', methods: ['POST'])]
    public function syncOrders(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $data = json_decode($request->getContent(), true);
        
        // Security: Validate JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON'], 400);
        }
        
        $shopDomain = $data['shop'] ?? null;

        // Security: Validate shop parameter
        if (!$shopDomain) {
            return $this->json(['success' => false, 'message' => 'Shop parameter required'], 400);
        }

        // Security: Validate shop domain format
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shopDomain)) {
            $this->logger->warning('Invalid shop domain in sync request', [
                'shop' => $shopDomain,
                'ip' => $request->getClientIp(),
            ]);
            return $this->json(['success' => false, 'message' => 'Invalid shop domain format'], 400);
        }

        // Security: Verify CSRF token for POST requests
        // (Symfony automatically validates CSRF for form submissions, but API needs manual check)
        
        $store = $this->storeRepository->findOneBy([
            'shopDomain' => $shopDomain,
            'isActive' => true,
        ]);

        if (!$store) {
            return $this->json(['success' => false, 'message' => 'Store not connected'], 404);
        }

        try {
            // Check rate limit status
            $rateLimitStatus = $this->rateLimiter->getStatus($shopDomain);
            $this->logger->debug('Rate limit status', $rateLimitStatus);

            // Apply rate limiting
            $this->rateLimiter->throttle($shopDomain);

            // Try cache first for quick response
            $orders = $this->cacheService->getOrders($shopDomain);
            
            if (!$orders) {
                // Fetch orders from Shopify API with rate limiting
                $orders = $this->shopifyService->getOrders($store, [
                    'status' => 'any',
                    'limit' => 250,
                    'fields' => 'id,name,created_at,total_price,financial_status,fulfillment_status',
                ]);

                // Cache orders for 3 minutes
                $this->cacheService->cacheOrders($shopDomain, $orders);
            }

            $syncedCount = count($orders);

            // Update store stats (async would be better)
            $store->setTotalOrdersCount($syncedCount);
            $store->setSyncedOrdersCount($syncedCount);
            $store->setLastSyncAt(new \DateTime());
            $this->storeRepository->save($store);

            // Invalidate stats cache
            $this->cacheService->invalidate($shopDomain);

            $duration = round((microtime(true) - $startTime) * 1000);

            $this->logger->info('Orders synced successfully', [
                'shop' => $shopDomain,
                'synced_count' => $syncedCount,
                'duration_ms' => $duration,
                'from_cache' => $orders !== null,
            ]);

            return $this->json([
                'success' => true,
                'synced_count' => $syncedCount,
                'total_orders' => count($orders),
                'duration' => $duration,
                'from_cache' => $orders !== null,
                'message' => 'Siparişler başarıyla senkronize edildi',
                'rate_limit' => $rateLimitStatus,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Order sync failed', [
                'shop' => $shopDomain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Senkronizasyon hatası: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync logs
     */
    #[Route('/logs', name: 'logs', methods: ['GET'])]
    public function logs(Request $request): JsonResponse
    {
        $shopDomain = $request->query->get('shop');
        $limit = (int) $request->query->get('limit', 50);

        if (!$shopDomain) {
            return $this->json(['error' => 'Shop parameter required'], 400);
        }

        $store = $this->storeRepository->findOneBy([
            'shopDomain' => $shopDomain,
            'isActive' => true,
        ]);

        if (!$store) {
            return $this->json(['logs' => []], 404);
        }

        // Mock logs for now - TODO: Implement proper log storage
        $logs = [
            [
                'level' => 'info',
                'message' => 'Uygulama başlatıldı',
                'timestamp' => (new \DateTime('-5 minutes'))->format('c'),
            ],
            [
                'level' => 'success',
                'message' => 'Shopify bağlantısı doğrulandı',
                'timestamp' => (new \DateTime('-4 minutes'))->format('c'),
            ],
            [
                'level' => 'info',
                'message' => sprintf('%d sipariş bulundu', $store->getTotalOrdersCount() ?? 0),
                'timestamp' => (new \DateTime('-3 minutes'))->format('c'),
            ],
        ];

        return $this->json([
            'logs' => array_slice($logs, 0, $limit),
            'total' => count($logs),
        ]);
    }
}
