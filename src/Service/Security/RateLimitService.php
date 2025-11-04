<?php

namespace App\Service\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class RateLimitService
{
    private FilesystemAdapter $cache;
    private bool $enabled;
    private int $perMinute;
    private int $perHour;

    public function __construct(
        bool $rateLimitEnabled,
        int $rateLimitPerMinute,
        int $rateLimitPerHour
    ) {
        $this->cache = new FilesystemAdapter('rate_limit', 3600);
        $this->enabled = $rateLimitEnabled;
        $this->perMinute = $rateLimitPerMinute;
        $this->perHour = $rateLimitPerHour;
    }

    /**
     * Check if request is rate limited
     * Returns true if allowed, false if rate limit exceeded
     */
    public function checkLimit(Request $request, string $identifier = null): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $identifier = $identifier ?? $this->getIdentifier($request);

        // Check minute limit
        if (!$this->checkMinuteLimit($identifier)) {
            return false;
        }

        // Check hour limit
        if (!$this->checkHourLimit($identifier)) {
            return false;
        }

        return true;
    }

    /**
     * Get rate limit info for headers
     */
    public function getRateLimitInfo(Request $request, string $identifier = null): array
    {
        if (!$this->enabled) {
            return [
                'limit' => 'unlimited',
                'remaining' => 'unlimited',
                'reset' => 0
            ];
        }

        $identifier = $identifier ?? $this->getIdentifier($request);

        $minuteKey = "minute_{$identifier}";
        $hourKey = "hour_{$identifier}";

        $minuteItem = $this->cache->getItem($minuteKey);
        $hourItem = $this->cache->getItem($hourKey);

        $minuteCount = $minuteItem->isHit() ? $minuteItem->get() : 0;
        $hourCount = $hourItem->isHit() ? $hourItem->get() : 0;

        $minuteRemaining = max(0, $this->perMinute - $minuteCount);
        $hourRemaining = max(0, $this->perHour - $hourCount);

        return [
            'limit' => $this->perMinute,
            'remaining' => min($minuteRemaining, $hourRemaining),
            'reset' => time() + 60 // Next minute
        ];
    }

    /**
     * Get identifier from request (IP address or user ID)
     */
    private function getIdentifier(Request $request): string
    {
        // Try to get Cloudflare real IP first
        if ($request->attributes->has('cloudflare_real_ip')) {
            return $request->attributes->get('cloudflare_real_ip');
        }

        // Fallback to standard methods
        return $request->getClientIp() ?? 'unknown';
    }

    /**
     * Check minute rate limit
     */
    private function checkMinuteLimit(string $identifier): bool
    {
        $key = "minute_{$identifier}";
        
        return $this->cache->get($key, function (ItemInterface $item) {
            $item->expiresAfter(60); // 1 minute
            return 0;
        }) < $this->perMinute;
    }

    /**
     * Check hour rate limit
     */
    private function checkHourLimit(string $identifier): bool
    {
        $key = "hour_{$identifier}";
        
        return $this->cache->get($key, function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour
            return 0;
        }) < $this->perHour;
    }

    /**
     * Increment rate limit counters
     */
    public function incrementCounter(Request $request, string $identifier = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $identifier = $identifier ?? $this->getIdentifier($request);

        // Increment minute counter
        $minuteKey = "minute_{$identifier}";
        $minuteItem = $this->cache->getItem($minuteKey);
        $minuteCount = $minuteItem->isHit() ? $minuteItem->get() : 0;
        $minuteItem->set($minuteCount + 1);
        $minuteItem->expiresAfter(60);
        $this->cache->save($minuteItem);

        // Increment hour counter
        $hourKey = "hour_{$identifier}";
        $hourItem = $this->cache->getItem($hourKey);
        $hourCount = $hourItem->isHit() ? $hourItem->get() : 0;
        $hourItem->set($hourCount + 1);
        $hourItem->expiresAfter(3600);
        $this->cache->save($hourItem);
    }

    /**
     * Clear rate limit for identifier
     */
    public function clearLimit(string $identifier): void
    {
        $this->cache->deleteItem("minute_{$identifier}");
        $this->cache->deleteItem("hour_{$identifier}");
    }
}
