<?php

namespace App\Service\Cloudflare;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CloudflareRateLimiter
{
    private const API_BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiToken,
        private string $zoneId,
        private bool $enabled = true
    ) {}

    /**
     * Create rate limit rule
     */
    public function createRateLimit(array $config): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        // Default configuration
        $defaultConfig = [
            'threshold' => 100,
            'period' => 60,
            'action' => [
                'mode' => 'ban',
                'timeout' => 3600,
            ],
            'match' => [
                'request' => [
                    'url' => '*',
                ],
            ],
            'disabled' => false,
            'description' => 'Rate limit rule',
        ];

        $ruleConfig = array_merge($defaultConfig, $config);

        try {
            $response = $this->httpClient->request('POST', self::API_BASE_URL . "/zones/{$this->zoneId}/rate_limits", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $ruleConfig,
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
     * Create API endpoint rate limit
     */
    public function createApiRateLimit(
        string $endpoint,
        int $requestsPerMinute = 60,
        int $banDuration = 3600
    ): array {
        return $this->createRateLimit([
            'threshold' => $requestsPerMinute,
            'period' => 60,
            'action' => [
                'mode' => 'ban',
                'timeout' => $banDuration,
                'response' => [
                    'content_type' => 'application/json',
                    'body' => json_encode([
                        'success' => false,
                        'error' => 'Rate limit exceeded. Please try again later.',
                    ]),
                ],
            ],
            'match' => [
                'request' => [
                    'url' => "*{$endpoint}*",
                ],
            ],
            'description' => "Rate limit for {$endpoint}",
        ]);
    }

    /**
     * Create login rate limit
     */
    public function createLoginRateLimit(int $attemptsPerMinute = 5): array
    {
        return $this->createRateLimit([
            'threshold' => $attemptsPerMinute,
            'period' => 60,
            'action' => [
                'mode' => 'challenge',
                'timeout' => 900, // 15 minutes
            ],
            'match' => [
                'request' => [
                    'url' => '*/login*',
                    'methods' => ['POST'],
                ],
                'response' => [
                    'status' => [401, 403],
                ],
            ],
            'description' => 'Login rate limit - Failed attempts',
        ]);
    }

    /**
     * List all rate limit rules
     */
    public function listRateLimits(int $page = 1, int $perPage = 50): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . "/zones/{$this->zoneId}/rate_limits", [
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
     * Delete rate limit rule
     */
    public function deleteRateLimit(string $ruleId): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('DELETE', self::API_BASE_URL . "/zones/{$this->zoneId}/rate_limits/{$ruleId}", [
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
     * Update rate limit rule
     */
    public function updateRateLimit(string $ruleId, array $config): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Cloudflare integration disabled'];
        }

        try {
            $response = $this->httpClient->request('PUT', self::API_BASE_URL . "/zones/{$this->zoneId}/rate_limits/{$ruleId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $config,
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
}
