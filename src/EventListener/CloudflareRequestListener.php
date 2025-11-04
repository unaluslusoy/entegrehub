<?php

namespace App\EventListener;

use App\Service\Cloudflare\CloudflareService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class CloudflareRequestListener
{
    public function __construct(
        private CloudflareService $cloudflareService,
        private LoggerInterface $logger,
        private bool $enabled = true
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Get real IP from Cloudflare
        $realIp = $this->cloudflareService->getRealIpAddress($request);
        
        // Set real IP to request attributes for later use
        $request->attributes->set('cloudflare_real_ip', $realIp);

        // Get visitor info
        $visitorInfo = $this->cloudflareService->getVisitorInfo($request);
        $request->attributes->set('cloudflare_visitor_info', $visitorInfo);

        // Log Cloudflare request info
        if ($this->cloudflareService->isCloudflareRequest($request)) {
            $this->logger->info('Cloudflare request detected', [
                'ip' => $realIp,
                'ray_id' => $visitorInfo['ray_id'],
                'country' => $visitorInfo['country'],
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
            ]);
        }

        // Security: Block known malicious patterns
        $this->checkSecurityPatterns($request, $realIp);
    }

    private function checkSecurityPatterns($request, string $ip): void
    {
        $path = $request->getPathInfo();
        $userAgent = $request->headers->get('User-Agent', '');

        // Check for common attack patterns
        $suspiciousPatterns = [
            '/\.env',
            '/\.git',
            '/phpMyAdmin',
            '/admin.php',
            '/wp-admin',
            '/wp-login',
            '/.aws/',
            '/config.php',
            '/shell.php',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                $this->logger->warning('Suspicious request detected', [
                    'ip' => $ip,
                    'path' => $path,
                    'user_agent' => $userAgent,
                    'pattern' => $pattern,
                ]);

                // Could automatically block IP via Cloudflare here
                // $this->cloudflareService->addFirewallRule($ip, 'block', 'Auto-blocked: Suspicious activity');
                
                break;
            }
        }
    }
}
