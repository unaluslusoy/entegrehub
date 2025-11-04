<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * High-performance cache service for Shopify data
 */
class ShopifyCacheService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const STATS_CACHE_TTL = 60; // 1 minute for stats
    private const ORDERS_CACHE_TTL = 180; // 3 minutes for orders

    public function __construct(
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get cached data or execute callback
     */
    public function remember(string $key, callable $callback, int $ttl = self::CACHE_TTL): mixed
    {
        $cacheKey = $this->generateCacheKey($key);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $this->logger->debug('Cache hit', ['key' => $cacheKey]);
            return $item->get();
        }

        $this->logger->debug('Cache miss', ['key' => $cacheKey]);
        $value = $callback();

        $item->set($value);
        $item->expiresAfter($ttl);
        $this->cache->save($item);

        return $value;
    }

    /**
     * Cache store statistics
     */
    public function cacheStats(string $shopDomain, array $stats): void
    {
        $key = $this->generateCacheKey("stats_{$shopDomain}");
        $item = $this->cache->getItem($key);
        $item->set($stats);
        $item->expiresAfter(self::STATS_CACHE_TTL);
        $this->cache->save($item);
    }

    /**
     * Get cached stats
     */
    public function getStats(string $shopDomain): ?array
    {
        $key = $this->generateCacheKey("stats_{$shopDomain}");
        $item = $this->cache->getItem($key);
        
        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Cache orders list
     */
    public function cacheOrders(string $shopDomain, array $orders): void
    {
        $key = $this->generateCacheKey("orders_{$shopDomain}");
        $item = $this->cache->getItem($key);
        $item->set($orders);
        $item->expiresAfter(self::ORDERS_CACHE_TTL);
        $this->cache->save($item);
    }

    /**
     * Get cached orders
     */
    public function getOrders(string $shopDomain): ?array
    {
        $key = $this->generateCacheKey("orders_{$shopDomain}");
        $item = $this->cache->getItem($key);
        
        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Invalidate cache for a shop
     */
    public function invalidate(string $shopDomain): void
    {
        $patterns = [
            "stats_{$shopDomain}",
            "orders_{$shopDomain}",
            "shop_details_{$shopDomain}",
        ];

        foreach ($patterns as $pattern) {
            $key = $this->generateCacheKey($pattern);
            $this->cache->deleteItem($key);
        }

        $this->logger->info('Cache invalidated', ['shop' => $shopDomain]);
    }

    /**
     * Generate cache key with prefix
     */
    private function generateCacheKey(string $key): string
    {
        return 'shopify_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    }

    /**
     * Warm up cache for a shop
     */
    public function warmup(string $shopDomain, callable $statsCallback, callable $ordersCallback): void
    {
        // Cache stats
        $stats = $statsCallback();
        $this->cacheStats($shopDomain, $stats);

        // Cache orders
        $orders = $ordersCallback();
        $this->cacheOrders($shopDomain, $orders);

        $this->logger->info('Cache warmed up', ['shop' => $shopDomain]);
    }
}
