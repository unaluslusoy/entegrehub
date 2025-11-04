<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;

/**
 * Security Rate Limiter
 * Prevents brute force and DDoS attacks
 */
class SecurityRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const BLOCK_DURATION = 900; // 15 minutes
    private const CLEANUP_PROBABILITY = 0.01; // 1% chance to cleanup old entries

    private array $attempts = [];
    private array $blockedIps = [];

    public function __construct(
        private LoggerInterface $logger
    ) {
        $this->loadFromCache();
    }

    /**
     * Check if IP is rate limited
     */
    public function isRateLimited(Request $request, string $context = 'general'): bool
    {
        $ip = $this->getClientIp($request);
        $key = $this->getKey($ip, $context);

        // Cleanup old entries randomly
        if (mt_rand(1, 100) <= self::CLEANUP_PROBABILITY * 100) {
            $this->cleanup();
        }

        // Check if IP is blocked
        if (isset($this->blockedIps[$key])) {
            if (time() < $this->blockedIps[$key]) {
                $this->logger->warning('Rate limit exceeded', [
                    'ip' => $ip,
                    'context' => $context,
                    'blocked_until' => date('Y-m-d H:i:s', $this->blockedIps[$key])
                ]);
                return true;
            } else {
                // Block expired
                unset($this->blockedIps[$key]);
                unset($this->attempts[$key]);
            }
        }

        return false;
    }

    /**
     * Record failed attempt
     */
    public function recordAttempt(Request $request, string $context = 'general'): void
    {
        $ip = $this->getClientIp($request);
        $key = $this->getKey($ip, $context);

        if (!isset($this->attempts[$key])) {
            $this->attempts[$key] = [
                'count' => 0,
                'first_attempt' => time()
            ];
        }

        $this->attempts[$key]['count']++;
        $this->attempts[$key]['last_attempt'] = time();

        // Block if exceeded max attempts
        if ($this->attempts[$key]['count'] >= self::MAX_ATTEMPTS) {
            $this->blockedIps[$key] = time() + self::BLOCK_DURATION;
            
            $this->logger->warning('IP blocked due to rate limiting', [
                'ip' => $ip,
                'context' => $context,
                'attempts' => $this->attempts[$key]['count'],
                'blocked_until' => date('Y-m-d H:i:s', $this->blockedIps[$key])
            ]);
        }

        $this->saveToCache();
    }

    /**
     * Reset attempts for IP
     */
    public function reset(Request $request, string $context = 'general'): void
    {
        $ip = $this->getClientIp($request);
        $key = $this->getKey($ip, $context);

        unset($this->attempts[$key]);
        unset($this->blockedIps[$key]);
        $this->saveToCache();
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(Request $request, string $context = 'general'): int
    {
        $ip = $this->getClientIp($request);
        $key = $this->getKey($ip, $context);

        if (!isset($this->attempts[$key])) {
            return self::MAX_ATTEMPTS;
        }

        return max(0, self::MAX_ATTEMPTS - $this->attempts[$key]['count']);
    }

    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        // Check for CloudFlare
        if ($request->headers->has('CF-Connecting-IP')) {
            return $request->headers->get('CF-Connecting-IP');
        }

        // Check for other proxies
        if ($request->headers->has('X-Forwarded-For')) {
            $ips = explode(',', $request->headers->get('X-Forwarded-For'));
            return trim($ips[0]);
        }

        return $request->getClientIp() ?? '0.0.0.0';
    }

    /**
     * Generate cache key
     */
    private function getKey(string $ip, string $context): string
    {
        return md5($ip . ':' . $context);
    }

    /**
     * Cleanup old entries
     */
    private function cleanup(): void
    {
        $now = time();

        foreach ($this->blockedIps as $key => $expiry) {
            if ($expiry < $now) {
                unset($this->blockedIps[$key]);
                unset($this->attempts[$key]);
            }
        }

        foreach ($this->attempts as $key => $data) {
            if (isset($data['last_attempt']) && ($now - $data['last_attempt']) > self::BLOCK_DURATION) {
                unset($this->attempts[$key]);
            }
        }

        $this->saveToCache();
    }

    /**
     * Load from cache
     */
    private function loadFromCache(): void
    {
        $cacheFile = sys_get_temp_dir() . '/security_rate_limiter.cache';
        if (file_exists($cacheFile)) {
            $data = @unserialize(file_get_contents($cacheFile));
            if ($data !== false) {
                $this->attempts = $data['attempts'] ?? [];
                $this->blockedIps = $data['blocked'] ?? [];
            }
        }
    }

    /**
     * Save to cache
     */
    private function saveToCache(): void
    {
        $cacheFile = sys_get_temp_dir() . '/security_rate_limiter.cache';
        file_put_contents($cacheFile, serialize([
            'attempts' => $this->attempts,
            'blocked' => $this->blockedIps
        ]));
    }
}
