<?php

namespace App\Service\Cloudflare;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CloudflareService
{
    private const API_BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiToken,
        private string $zoneId,
        private bool $enabled = true
    ) {}

    /**
     * Get visitor's real IP address from Cloudflare headers
     */
    public function getRealIpAddress(\Symfony\Component\HttpFoundation\Request $request): string
    {
        // CF-Connecting-IP is the most reliable Cloudflare header
        if ($request->headers->has('CF-Connecting-IP')) {
            return $request->headers->get('CF-Connecting-IP');
        }

        // Fallback to other headers
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if ($request->server->has($header)) {
                $ip = $request->server->get($header);
                if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->getClientIp() ?? '0.0.0.0';
    }

    /**
     * Verify request is from Cloudflare
     */
    public function isCloudflareRequest(\Symfony\Component\HttpFoundation\Request $request): bool
    {
        // Check for Cloudflare headers
        return $request->headers->has('CF-RAY') || 
               $request->headers->has('CF-Connecting-IP');
    }

    /**
     * Get Cloudflare visitor information
     */
    public function getVisitorInfo(\Symfony\Component\HttpFoundation\Request $request): array
    {
        return [
            'ip' => $this->getRealIpAddress($request),
            'ray_id' => $request->headers->get('CF-RAY'),
            'country' => $request->headers->get('CF-IPCountry'),
            'is_cloudflare' => $this->isCloudflareRequest($request),
            'visitor_id' => $request->headers->get('CF-Visitor'),
            'device_type' => $request->headers->get('CF-Device-Type'),
        ];
    }

    /**
     * Add IP to firewall access rules (whitelist/blacklist)
     */
    public function addFirewallRule(
        string $ip,
        string $mode = 'block', // block, challenge, whitelist, js_challenge
        string $notes = ''
    ): array {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('POST', self::API_BASE_URL . "/zones/{$this->zoneId}/firewall/access_rules/rules", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'mode' => $mode,
                    'configuration' => [
                        'target' => 'ip',
                        'value' => $ip,
                    ],
                    'notes' => $notes ?: "Added by EntegreHub Kargo System",
                ],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? null,
                'errors' => $data['errors'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove IP from firewall rules
     */
    public function removeFirewallRule(string $ruleId): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('DELETE', self::API_BASE_URL . "/zones/{$this->zoneId}/firewall/access_rules/rules/{$ruleId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * List firewall rules
     */
    public function listFirewallRules(int $page = 1, int $perPage = 50): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . "/zones/{$this->zoneId}/firewall/access_rules/rules", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ],
                'query' => [
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? [],
                'result_info' => $data['result_info'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Purge cache for specific URLs
     */
    public function purgeCache(array $urls = []): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $payload = empty($urls) ? ['purge_everything' => true] : ['files' => $urls];

            $response = $this->httpClient->request('POST', self::API_BASE_URL . "/zones/{$this->zoneId}/purge_cache", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get zone analytics
     */
    public function getAnalytics(string $since = '-7d', string $until = 'now'): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . "/zones/{$this->zoneId}/analytics/dashboard", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ],
                'query' => [
                    'since' => $since,
                    'until' => $until,
                ],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get security events (firewall events)
     */
    public function getSecurityEvents(int $limit = 100): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . "/zones/{$this->zoneId}/security/events", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                ],
                'query' => [
                    'per_page' => $limit,
                ],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update security level
     */
    public function setSecurityLevel(string $level = 'medium'): array
    {
        // Levels: off, essentially_off, low, medium, high, under_attack
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('PATCH', self::API_BASE_URL . "/zones/{$this->zoneId}/settings/security_level", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'value' => $level,
                ],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Block country
     */
    public function blockCountry(string $countryCode, string $expression = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        $expr = $expression ?: "(ip.geoip.country eq \"{$countryCode}\")";

        try {
            $response = $this->httpClient->request('POST', self::API_BASE_URL . "/zones/{$this->zoneId}/firewall/rules", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [[
                    'filter' => [
                        'expression' => $expr,
                        'paused' => false,
                    ],
                    'action' => 'block',
                    'description' => "Block country: {$countryCode}",
                ]],
            ]);

            $data = $response->toArray();
            return [
                'success' => $data['success'] ?? false,
                'result' => $data['result'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enable "Under Attack" mode
     */
    public function enableUnderAttackMode(): array
    {
        return $this->setSecurityLevel('under_attack');
    }

    /**
     * Disable "Under Attack" mode (set to medium)
     */
    public function disableUnderAttackMode(): array
    {
        return $this->setSecurityLevel('medium');
    }
}
