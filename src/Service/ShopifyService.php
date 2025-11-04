<?php

namespace App\Service;

use App\Entity\ShopifyStore;
use App\Entity\User;
use App\Repository\ShopifyStoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Shopify OAuth & API Service
 */
class ShopifyService
{
    private const API_VERSION = '2024-10';
    private const OAUTH_SCOPES = 'read_orders,write_orders,read_products,write_products,read_fulfillments,write_fulfillments,read_shipping,write_shipping,read_customers,read_inventory';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ShopifyStoreRepository $shopifyStoreRepository,
        private LoggerInterface $logger,
        private ShopifyRateLimiter $rateLimiter,
        private string $shopifyApiKey,
        private string $shopifyApiSecret,
        private string $appUrl
    ) {}

    /**
     * Generate OAuth authorization URL
     */
    public function getAuthorizationUrl(string $shopDomain, ?string $redirectUri = null): string
    {
        $redirectUri = $redirectUri ?? $this->getRedirectUri();
        
        $state = bin2hex(random_bytes(16));
        
        $params = [
            'client_id' => $this->shopifyApiKey,
            'scope' => self::OAUTH_SCOPES,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'grant_options[]' => 'per-user'
        ];

        $authUrl = sprintf(
            'https://%s/admin/oauth/authorize?%s',
            $shopDomain,
            http_build_query($params)
        );

        $this->logger->info('Generated Shopify OAuth URL', [
            'shop' => $shopDomain,
            'redirect_uri' => $redirectUri,
            'scopes' => self::OAUTH_SCOPES
        ]);

        return $authUrl;
    }

    /**
     * Get redirect URI for OAuth
     */
    public function getRedirectUri(): string
    {
        return $this->appUrl . '/user/shopify/callback';
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $shopDomain, string $code): array
    {
        try {
            $response = $this->httpClient->request('POST', sprintf(
                'https://%s/admin/oauth/access_token',
                $shopDomain
            ), [
                'json' => [
                    'client_id' => $this->shopifyApiKey,
                    'client_secret' => $this->shopifyApiSecret,
                    'code' => $code
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token'])) {
                throw new \Exception('Access token not returned by Shopify');
            }

            return [
                'access_token' => $data['access_token'],
                'scope' => $data['scope'] ?? self::OAUTH_SCOPES
            ];
        } catch (\Exception $e) {
            $this->logger->error('Shopify OAuth token exchange failed', [
                'shop' => $shopDomain,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Failed to exchange authorization code: ' . $e->getMessage());
        }
    }

    /**
     * Create or update store connection
     */
    public function createOrUpdateStore(User $user, string $shopDomain, string $accessToken, string $scopes): ShopifyStore
    {
        $store = $this->shopifyStoreRepository->findByUserAndDomain($user, $shopDomain);

        if (!$store) {
            $store = new ShopifyStore();
            $store->setUser($user);
            $store->setShopDomain($shopDomain);
        }

        $store->setAccessToken($accessToken);
        $store->setScopes($scopes);
        $store->setApiKey($this->shopifyApiKey);
        $store->setIsActive(true);
        $store->setUpdatedAt(new \DateTime());

        // Fetch shop details
        try {
            $shopDetails = $this->getShopDetails($store);
            $store->setShopName($shopDetails['name'] ?? null);
            $store->setShopEmail($shopDetails['email'] ?? null);
            $store->setShopCurrency($shopDetails['currency'] ?? null);
            $store->setShopTimezone($shopDetails['timezone'] ?? null);
            $store->setShopPlan($shopDetails['plan_name'] ?? null);
            $store->setMetadata($shopDetails);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to fetch shop details', [
                'shop' => $shopDomain,
                'error' => $e->getMessage()
            ]);
        }

        $this->entityManager->persist($store);
        $this->entityManager->flush();

        return $store;
    }

    /**
     * Get shop details from Shopify API
     */
    public function getShopDetails(ShopifyStore $store): array
    {
        $response = $this->makeApiRequest($store, 'GET', '/admin/api/' . self::API_VERSION . '/shop.json');
        return $response['shop'] ?? [];
    }

    /**
     * Make API request to Shopify
     */
    public function makeApiRequest(ShopifyStore $store, string $method, string $endpoint, array $data = []): array
    {
        try {
            // Apply rate limiting before request
            $this->rateLimiter->throttle($store->getShopDomain());
            
            $url = sprintf('https://%s%s', $store->getShopDomain(), $endpoint);
            
            $options = [
                'headers' => [
                    'X-Shopify-Access-Token' => $store->getAccessToken(),
                    'Content-Type' => 'application/json',
                ]
            ];

            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }

            if (!empty($data) && $method === 'GET') {
                $url .= '?' . http_build_query($data);
            }

            $startTime = microtime(true);
            $response = $this->httpClient->request($method, $url, $options);
            $duration = round((microtime(true) - $startTime) * 1000);

            $this->logger->debug('Shopify API request completed', [
                'shop' => $store->getShopDomain(),
                'endpoint' => $endpoint,
                'method' => $method,
                'duration_ms' => $duration,
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Shopify API request failed', [
                'shop' => $store->getShopDomain(),
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException('Shopify API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify webhook HMAC signature
     */
    public function verifyWebhook(Request $request): bool
    {
        $hmacHeader = $request->headers->get('X-Shopify-Hmac-Sha256');
        
        if (!$hmacHeader) {
            return false;
        }

        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $this->shopifyApiSecret, true));

        return hash_equals($calculatedHmac, $hmacHeader);
    }

    /**
     * Register webhook
     */
    public function registerWebhook(ShopifyStore $store, string $topic, string $address): array
    {
        $data = [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json'
            ]
        ];

        return $this->makeApiRequest(
            $store,
            'POST',
            '/admin/api/' . self::API_VERSION . '/webhooks.json',
            $data
        );
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(ShopifyStore $store, string $webhookId): void
    {
        $this->makeApiRequest(
            $store,
            'DELETE',
            '/admin/api/' . self::API_VERSION . '/webhooks/' . $webhookId . '.json'
        );
    }

    /**
     * Get all webhooks
     */
    public function getWebhooks(ShopifyStore $store): array
    {
        $response = $this->makeApiRequest(
            $store,
            'GET',
            '/admin/api/' . self::API_VERSION . '/webhooks.json'
        );

        return $response['webhooks'] ?? [];
    }

    /**
     * Test API connection
     */
    public function testConnection(ShopifyStore $store): bool
    {
        try {
            $this->getShopDetails($store);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Shopify connection test failed', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify HMAC signature from Shopify
     */
    public function verifyHmac(array $queryParams, ?string $hmac): bool
    {
        if (!$hmac) {
            return false;
        }

        // Remove hmac and signature from params
        $params = $queryParams;
        unset($params['hmac'], $params['signature']);

        // Sort params alphabetically
        ksort($params);

        // Build query string
        $queryString = http_build_query($params);

        // Calculate expected HMAC
        $calculatedHmac = hash_hmac('sha256', $queryString, $this->shopifyApiSecret);

        // Compare in constant time to prevent timing attacks
        return hash_equals($calculatedHmac, $hmac);
    }

    /**
     * Disconnect store
     */
    public function disconnectStore(ShopifyStore $store): void
    {
        $store->setIsActive(false);
        $this->entityManager->flush();
    }

    /**
     * Get orders from Shopify
     */
    public function getOrders(ShopifyStore $store, array $filters = []): array
    {
        $params = array_merge([
            'status' => 'any',
            'limit' => 50
        ], $filters);

        $response = $this->makeApiRequest(
            $store,
            'GET',
            '/admin/api/' . self::API_VERSION . '/orders.json',
            $params
        );

        return $response['orders'] ?? [];
    }

    /**
     * Get single order
     */
    public function getOrder(ShopifyStore $store, string $orderId): ?array
    {
        try {
            $response = $this->makeApiRequest(
                $store,
                'GET',
                '/admin/api/' . self::API_VERSION . '/orders/' . $orderId . '.json'
            );

            return $response['order'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update order fulfillment
     */
    public function updateFulfillment(ShopifyStore $store, string $orderId, array $fulfillmentData): array
    {
        return $this->makeApiRequest(
            $store,
            'POST',
            '/admin/api/' . self::API_VERSION . '/orders/' . $orderId . '/fulfillments.json',
            ['fulfillment' => $fulfillmentData]
        );
    }

    /**
     * Get order count
     */
    public function getOrderCount(ShopifyStore $store, array $filters = []): int
    {
        $response = $this->makeApiRequest(
            $store,
            'GET',
            '/admin/api/' . self::API_VERSION . '/orders/count.json',
            $filters
        );

        return $response['count'] ?? 0;
    }
}
