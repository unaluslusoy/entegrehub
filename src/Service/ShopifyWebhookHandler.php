<?php

namespace App\Service;

use App\Entity\ShopifyStore;
use App\Entity\ShopifyWebhook;
use App\Repository\ShopifyWebhookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Shopify Webhook Handler Service
 */
class ShopifyWebhookHandler
{
    private const SUPPORTED_TOPICS = [
        'orders/create',
        'orders/updated',
        'orders/cancelled',
        'orders/fulfilled',
        'orders/paid',
        'fulfillments/create',
        'fulfillments/update',
        'products/create',
        'products/update',
        'products/delete',
        'app/uninstalled'
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopifyWebhookRepository $webhookRepository,
        private ShopifySyncService $syncService,
        private LoggerInterface $logger
    ) {}

    /**
     * Process incoming webhook
     */
    public function handleWebhook(Request $request, ShopifyStore $store): array
    {
        $topic = $request->headers->get('X-Shopify-Topic');
        $shopDomain = $request->headers->get('X-Shopify-Shop-Domain');
        $webhookId = $request->headers->get('X-Shopify-Webhook-Id');

        if (!$topic || !$shopDomain) {
            throw new \InvalidArgumentException('Missing required Shopify webhook headers');
        }

        // Verify shop domain matches
        if ($shopDomain !== $store->getShopDomain()) {
            throw new \InvalidArgumentException('Shop domain mismatch');
        }

        $payload = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload');
        }

        $this->logger->info('Shopify webhook received', [
            'topic' => $topic,
            'shop' => $shopDomain,
            'webhook_id' => $webhookId
        ]);

        // Route to appropriate handler
        $result = match ($topic) {
            'orders/create' => $this->handleOrderCreate($store, $payload),
            'orders/updated' => $this->handleOrderUpdate($store, $payload),
            'orders/cancelled' => $this->handleOrderCancel($store, $payload),
            'orders/fulfilled' => $this->handleOrderFulfilled($store, $payload),
            'orders/paid' => $this->handleOrderPaid($store, $payload),
            'app/uninstalled' => $this->handleAppUninstall($store, $payload),
            default => ['status' => 'ignored', 'message' => 'Topic not handled']
        };

        return array_merge($result, [
            'topic' => $topic,
            'webhook_id' => $webhookId
        ]);
    }

    /**
     * Handle orders/create webhook
     */
    private function handleOrderCreate(ShopifyStore $store, array $payload): array
    {
        try {
            $orderId = $payload['id'] ?? null;
            $orderNumber = $payload['order_number'] ?? $payload['name'] ?? null;

            if (!$orderId) {
                throw new \Exception('Order ID missing in payload');
            }

            // Sync this specific order
            $result = $this->syncService->syncSingleOrder($store, (string)$orderId, $payload);

            return [
                'status' => 'success',
                'message' => 'Order created and synced',
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'synced' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle orders/create webhook', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle orders/updated webhook
     */
    private function handleOrderUpdate(ShopifyStore $store, array $payload): array
    {
        try {
            $orderId = $payload['id'] ?? null;

            if (!$orderId) {
                throw new \Exception('Order ID missing in payload');
            }

            // Update existing order mapping
            $result = $this->syncService->updateOrderMapping($store, (string)$orderId, $payload);

            return [
                'status' => 'success',
                'message' => 'Order updated',
                'order_id' => $orderId,
                'updated' => $result
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle orders/updated webhook', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle orders/cancelled webhook
     */
    private function handleOrderCancel(ShopifyStore $store, array $payload): array
    {
        try {
            $orderId = $payload['id'] ?? null;

            if (!$orderId) {
                throw new \Exception('Order ID missing in payload');
            }

            // Mark order as cancelled
            $result = $this->syncService->cancelOrder($store, (string)$orderId);

            return [
                'status' => 'success',
                'message' => 'Order cancelled',
                'order_id' => $orderId
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle orders/cancelled webhook', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle orders/fulfilled webhook
     */
    private function handleOrderFulfilled(ShopifyStore $store, array $payload): array
    {
        try {
            $orderId = $payload['id'] ?? null;

            return [
                'status' => 'success',
                'message' => 'Order fulfilled notification received',
                'order_id' => $orderId
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle orders/paid webhook
     */
    private function handleOrderPaid(ShopifyStore $store, array $payload): array
    {
        try {
            $orderId = $payload['id'] ?? null;

            return [
                'status' => 'success',
                'message' => 'Order paid notification received',
                'order_id' => $orderId
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle app/uninstalled webhook
     */
    private function handleAppUninstall(ShopifyStore $store, array $payload): array
    {
        try {
            $store->setIsActive(false);
            $this->entityManager->flush();

            $this->logger->warning('Shopify app uninstalled', [
                'store_id' => $store->getId(),
                'shop' => $store->getShopDomain()
            ]);

            return [
                'status' => 'success',
                'message' => 'Store disconnected due to app uninstall'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Register webhooks for store
     */
    public function registerWebhooks(ShopifyStore $store, ShopifyService $shopifyService, string $webhookBaseUrl): array
    {
        $results = [];
        $topics = ['orders/create', 'orders/updated', 'orders/cancelled', 'app/uninstalled'];

        foreach ($topics as $topic) {
            try {
                // Check if webhook already exists
                $existing = $this->webhookRepository->findByStoreAndTopic($store, $topic);

                if ($existing) {
                    $results[$topic] = ['status' => 'exists', 'id' => $existing->getWebhookId()];
                    continue;
                }

                // Register webhook with Shopify
                $address = $webhookBaseUrl . '/user/shopify/webhook';
                $response = $shopifyService->registerWebhook($store, $topic, $address);

                if (isset($response['webhook'])) {
                    $webhookData = $response['webhook'];
                    
                    // Save to database
                    $webhook = new ShopifyWebhook();
                    $webhook->setStore($store);
                    $webhook->setTopic($topic);
                    $webhook->setWebhookId((string)$webhookData['id']);
                    $webhook->setAddress($address);
                    $webhook->setFormat('json');
                    $webhook->setIsActive(true);

                    $this->entityManager->persist($webhook);
                    
                    $results[$topic] = ['status' => 'created', 'id' => $webhookData['id']];
                } else {
                    $results[$topic] = ['status' => 'failed', 'error' => 'Invalid response from Shopify'];
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to register webhook', [
                    'store_id' => $store->getId(),
                    'topic' => $topic,
                    'error' => $e->getMessage()
                ]);
                
                $results[$topic] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $this->entityManager->flush();

        return $results;
    }

    /**
     * Unregister all webhooks for store
     */
    public function unregisterWebhooks(ShopifyStore $store, ShopifyService $shopifyService): array
    {
        $results = [];
        $webhooks = $store->getWebhooks();

        foreach ($webhooks as $webhook) {
            try {
                $shopifyService->deleteWebhook($store, $webhook->getWebhookId());
                $this->entityManager->remove($webhook);
                
                $results[] = [
                    'topic' => $webhook->getTopic(),
                    'status' => 'deleted'
                ];
            } catch (\Exception $e) {
                $this->logger->error('Failed to unregister webhook', [
                    'webhook_id' => $webhook->getId(),
                    'error' => $e->getMessage()
                ]);
                
                $results[] = [
                    'topic' => $webhook->getTopic(),
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        $this->entityManager->flush();

        return $results;
    }
}
