<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Shopify API Rate Limiter
 * Prevents hitting Shopify's rate limits (2 req/sec for REST API)
 */
class ShopifyRateLimiter
{
    private const MAX_REQUESTS_PER_SECOND = 2;
    private const BURST_CAPACITY = 40; // Token bucket capacity
    private const REFILL_RATE = 2; // Tokens per second

    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Wait if necessary before making API request
     */
    public function throttle(string $shopDomain): void
    {
        $key = "rate_limit_{$shopDomain}";
        
        $bucket = $this->cache->get($key, function() {
            return [
                'tokens' => self::BURST_CAPACITY,
                'last_refill' => microtime(true),
            ];
        });

        // Calculate tokens to add based on time passed
        $now = microtime(true);
        $timePassed = $now - $bucket['last_refill'];
        $tokensToAdd = floor($timePassed * self::REFILL_RATE);

        if ($tokensToAdd > 0) {
            $bucket['tokens'] = min(
                self::BURST_CAPACITY,
                $bucket['tokens'] + $tokensToAdd
            );
            $bucket['last_refill'] = $now;
        }

        // If no tokens available, wait
        if ($bucket['tokens'] < 1) {
            $waitTime = (1 - $bucket['tokens']) / self::REFILL_RATE;
            $this->logger->debug('Rate limit - waiting', [
                'shop' => $shopDomain,
                'wait_ms' => round($waitTime * 1000),
            ]);
            usleep((int)($waitTime * 1000000));
            $bucket['tokens'] = 0;
        } else {
            $bucket['tokens'] -= 1;
        }

        // Save bucket state
        $this->cache->set($key, $bucket, 60);
    }

    /**
     * Check if rate limit would be hit
     */
    public function canMakeRequest(string $shopDomain): bool
    {
        $key = "rate_limit_{$shopDomain}";
        
        $bucket = $this->cache->get($key, function() {
            return [
                'tokens' => self::BURST_CAPACITY,
                'last_refill' => microtime(true),
            ];
        });

        $now = microtime(true);
        $timePassed = $now - $bucket['last_refill'];
        $tokensToAdd = floor($timePassed * self::REFILL_RATE);
        $currentTokens = min(self::BURST_CAPACITY, $bucket['tokens'] + $tokensToAdd);

        return $currentTokens >= 1;
    }

    /**
     * Get current rate limit status
     */
    public function getStatus(string $shopDomain): array
    {
        $key = "rate_limit_{$shopDomain}";
        
        $bucket = $this->cache->get($key, function() {
            return [
                'tokens' => self::BURST_CAPACITY,
                'last_refill' => microtime(true),
            ];
        });

        $now = microtime(true);
        $timePassed = $now - $bucket['last_refill'];
        $tokensToAdd = floor($timePassed * self::REFILL_RATE);
        $currentTokens = min(self::BURST_CAPACITY, $bucket['tokens'] + $tokensToAdd);

        return [
            'available_tokens' => $currentTokens,
            'max_tokens' => self::BURST_CAPACITY,
            'refill_rate' => self::REFILL_RATE,
            'percentage' => round(($currentTokens / self::BURST_CAPACITY) * 100),
        ];
    }
}
