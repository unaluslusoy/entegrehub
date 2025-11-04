<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use App\Security\SecurityRateLimiter;
use Psr\Log\LoggerInterface;

/**
 * Security Headers and Rate Limiting Event Listener
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
class SecurityListener
{
    // Suspicious patterns in requests
    private const SUSPICIOUS_PATTERNS = [
        '/\.\.\//i',                    // Directory traversal
        '/<script/i',                   // XSS attempts
        '/union.*select/i',             // SQL injection
        '/base64_decode/i',             // Code injection
        '/eval\(/i',                    // Code injection
        '/system\(/i',                  // Command injection
        '/etc\/passwd/i',               // File disclosure
        '/cmd\.exe/i',                  // Windows command injection
        '/wget|curl/i',                 // Remote file inclusion
    ];

    public function __construct(
        private SecurityRateLimiter $rateLimiter,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // 1. Check for suspicious patterns
        if ($this->detectSuspiciousActivity($request)) {
            $this->logger->critical('Suspicious activity detected', [
                'ip' => $request->getClientIp(),
                'uri' => $request->getRequestUri(),
                'user_agent' => $request->headers->get('User-Agent'),
            ]);
            
            // Record multiple attempts to trigger block faster
            for ($i = 0; $i < 3; $i++) {
                $this->rateLimiter->recordAttempt($request, 'security');
            }
        }

        // 2. Rate limiting for sensitive endpoints
        if ($this->isSensitiveEndpoint($request)) {
            if ($this->rateLimiter->isRateLimited($request, 'auth')) {
                throw new TooManyRequestsHttpException(900, 'Too many requests. Please try again later.');
            }
        }

        // 3. Validate request size
        if ($this->isRequestTooBig($request)) {
            $this->logger->warning('Request too large', [
                'ip' => $request->getClientIp(),
                'size' => $request->headers->get('Content-Length'),
            ]);
            throw new TooManyRequestsHttpException(null, 'Request payload too large');
        }

        // 4. Check for bot/scanner user agents
        if ($this->isSuspiciousUserAgent($request)) {
            $this->logger->info('Suspicious user agent', [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
            ]);
        }
    }

    /**
     * Detect suspicious activity in request
     */
    private function detectSuspiciousActivity($request): bool
    {
        $uri = $request->getRequestUri();
        $queryString = $request->getQueryString() ?? '';
        $content = (string) $request->getContent();

        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $uri . $queryString . $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if endpoint is sensitive
     */
    private function isSensitiveEndpoint($request): bool
    {
        $path = $request->getPathInfo();
        
        $sensitivePatterns = [
            '/^\/login/',
            '/^\/register/',
            '/^\/reset-password/',
            '/^\/api\/login/',
            '/^\/user\/shopify\/callback/',
            '/^\/oauth/',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request is too big
     */
    private function isRequestTooBig($request): bool
    {
        $contentLength = $request->headers->get('Content-Length', 0);
        $maxSize = 10 * 1024 * 1024; // 10MB

        return $contentLength > $maxSize;
    }

    /**
     * Check for suspicious user agents
     */
    private function isSuspiciousUserAgent($request): bool
    {
        $userAgent = strtolower($request->headers->get('User-Agent', ''));
        
        $suspiciousAgents = [
            'scanner',
            'bot',
            'crawler',
            'spider',
            'scraper',
            'nikto',
            'sqlmap',
            'nmap',
            'masscan',
        ];

        foreach ($suspiciousAgents as $agent) {
            if (str_contains($userAgent, $agent)) {
                return true;
            }
        }

        return false;
    }
}
