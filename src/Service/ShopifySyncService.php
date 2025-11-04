<?php

namespace App\Service;

use App\Entity\ShopifyStore;
use App\Entity\ShopifySyncLog;
use App\Entity\ShopifyOrderMapping;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Address;
use App\Repository\ShopifyOrderMappingRepository;
use App\Repository\ShopifySyncLogRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Shopify Sync Service
 */
class ShopifySyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopifyService $shopifyService,
        private ShopifyOrderMappingRepository $orderMappingRepository,
        private ShopifySyncLogRepository $syncLogRepository,
        private OrderRepository $orderRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Sync all orders from Shopify
     */
    public function syncOrders(ShopifyStore $store, array $filters = []): ShopifySyncLog
    {
        // Create sync log
        $syncLog = new ShopifySyncLog();
        $syncLog->setStore($store);
        $syncLog->setSyncType('orders');
        $syncLog->setStatus('started');
        $syncLog->setStartedAt(new \DateTime());
        $this->entityManager->persist($syncLog);
        $this->entityManager->flush();

        try {
            // Update store sync status
            $store->setSyncStatus('syncing');
            $store->setSyncProgress(0);
            $this->entityManager->flush();

            // Get order count
            $totalOrders = $this->shopifyService->getOrderCount($store, $filters);
            $syncLog->setRecordsTotal($totalOrders);
            $this->entityManager->flush();

            $syncedCount = 0;
            $failedCount = 0;
            $page = 1;
            $limit = 50;

            // Fetch orders in batches
            while (true) {
                $orders = $this->shopifyService->getOrders($store, array_merge($filters, [
                    'limit' => $limit,
                    'page' => $page
                ]));

                if (empty($orders)) {
                    break;
                }

                foreach ($orders as $orderData) {
                    try {
                        $this->syncSingleOrder($store, (string)$orderData['id'], $orderData);
                        $syncedCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                        $this->logger->error('Failed to sync order', [
                            'store_id' => $store->getId(),
                            'shopify_order_id' => $orderData['id'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                    }

                    // Update progress
                    $progress = $totalOrders > 0 ? round(($syncedCount + $failedCount) / $totalOrders * 100) : 0;
                    $store->setSyncProgress($progress);
                    $syncLog->setRecordsSynced($syncedCount);
                    $syncLog->setRecordsFailed($failedCount);
                    $this->entityManager->flush();
                }

                if (count($orders) < $limit) {
                    break;
                }

                $page++;
            }

            // Update sync log
            $syncLog->setStatus('completed');
            $syncLog->setCompletedAt(new \DateTime());

            // Update store
            $store->setSyncStatus('completed');
            $store->setSyncProgress(100);
            $store->setLastSyncAt(new \DateTime());
            $store->setLastOrderSyncAt(new \DateTime());
            $store->setTotalOrdersSynced($store->getTotalOrdersSynced() + $syncedCount);

            $this->entityManager->flush();

            $this->logger->info('Shopify order sync completed', [
                'store_id' => $store->getId(),
                'synced' => $syncedCount,
                'failed' => $failedCount,
                'total' => $totalOrders
            ]);

        } catch (\Exception $e) {
            $syncLog->setStatus('failed');
            $syncLog->setErrorMessage($e->getMessage());
            $syncLog->setCompletedAt(new \DateTime());

            $store->setSyncStatus('failed');

            $this->entityManager->flush();

            $this->logger->error('Shopify order sync failed', [
                'store_id' => $store->getId(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }

        return $syncLog;
    }

    /**
     * Sync single order
     */
    public function syncSingleOrder(ShopifyStore $store, string $shopifyOrderId, ?array $orderData = null): bool
    {
        try {
            // Fetch order data if not provided
            if (!$orderData) {
                $orderData = $this->shopifyService->getOrder($store, $shopifyOrderId);
                if (!$orderData) {
                    throw new \Exception('Order not found in Shopify');
                }
            }

            // Check if mapping already exists
            $mapping = $this->orderMappingRepository->findByShopifyOrderId($store, $shopifyOrderId);

            if (!$mapping) {
                $mapping = new ShopifyOrderMapping();
                $mapping->setStore($store);
                $mapping->setShopifyOrderId($shopifyOrderId);
                $mapping->setShopifyOrderNumber($orderData['order_number'] ?? $orderData['name'] ?? '');
            }

            // Update mapping data
            $mapping->setShopifyData($orderData);
            $mapping->setLastSyncAt(new \DateTime());

            // Create/update internal order
            $internalOrder = $this->createOrUpdateInternalOrder($store, $orderData, $mapping);

            if ($internalOrder) {
                $mapping->setInternalOrderId($internalOrder->getId());
                $mapping->setSyncStatus('synced');
            } else {
                $mapping->setSyncStatus('failed');
            }

            $this->entityManager->persist($mapping);
            $this->entityManager->flush();

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to sync single order', [
                'store_id' => $store->getId(),
                'shopify_order_id' => $shopifyOrderId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create or update internal order from Shopify data
     */
    private function createOrUpdateInternalOrder(ShopifyStore $store, array $orderData, ShopifyOrderMapping $mapping): ?Order
    {
        try {
            $order = null;

            // Check if order already exists
            if ($mapping->getInternalOrderId()) {
                $order = $this->orderRepository->find($mapping->getInternalOrderId());
            }

            if (!$order) {
                $order = new Order();
                $order->setUser($store->getUser());
                $order->setOrderNumber($this->generateOrderNumber());
            }

            // Map Shopify data to internal order
            $order->setCustomerName($orderData['customer']['first_name'] ?? '' . ' ' . $orderData['customer']['last_name'] ?? '');
            $order->setCustomerEmail($orderData['customer']['email'] ?? null);
            $order->setCustomerPhone($orderData['customer']['phone'] ?? $orderData['shipping_address']['phone'] ?? null);
            $order->setTotalAmount((float)($orderData['total_price'] ?? 0));
            $order->setCurrency($orderData['currency'] ?? 'TRY');
            $order->setStatus($this->mapShopifyStatus($orderData['financial_status'] ?? 'pending'));
            $order->setOrderDate(new \DateTime($orderData['created_at'] ?? 'now'));
            $order->setShopifyOrderId((string)$orderData['id']);

            // Create shipping address
            if (isset($orderData['shipping_address'])) {
                $address = $this->createAddressFromShopifyData($orderData['shipping_address']);
                $this->entityManager->persist($address);
                $order->setShippingAddress($address);
            }

            // Create billing address
            if (isset($orderData['billing_address'])) {
                $billingAddress = $this->createAddressFromShopifyData($orderData['billing_address']);
                $this->entityManager->persist($billingAddress);
                $order->setBillingAddress($billingAddress);
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // Sync order items
            if (isset($orderData['line_items'])) {
                $this->syncOrderItems($order, $orderData['line_items']);
            }

            return $order;

        } catch (\Exception $e) {
            $this->logger->error('Failed to create internal order', [
                'shopify_order_id' => $orderData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Sync order items
     */
    private function syncOrderItems(Order $order, array $lineItems): void
    {
        foreach ($lineItems as $itemData) {
            $orderItem = new OrderItem();
            $orderItem->setOrderEntity($order);
            $orderItem->setProductName($itemData['name'] ?? '');
            $orderItem->setSku($itemData['sku'] ?? null);
            $orderItem->setQuantity((int)($itemData['quantity'] ?? 1));
            $orderItem->setPrice((float)($itemData['price'] ?? 0));
            $orderItem->setTotal((float)($itemData['price'] ?? 0) * (int)($itemData['quantity'] ?? 1));

            $this->entityManager->persist($orderItem);
        }

        $this->entityManager->flush();
    }

    /**
     * Create address from Shopify data
     */
    private function createAddressFromShopifyData(array $addressData): Address
    {
        $address = new Address();
        $address->setFirstName($addressData['first_name'] ?? '');
        $address->setLastName($addressData['last_name'] ?? '');
        $address->setCompany($addressData['company'] ?? null);
        $address->setAddress1($addressData['address1'] ?? '');
        $address->setAddress2($addressData['address2'] ?? null);
        $address->setCity($addressData['city'] ?? '');
        $address->setState($addressData['province'] ?? null);
        $address->setPostalCode($addressData['zip'] ?? '');
        $address->setCountry($addressData['country'] ?? 'TR');
        $address->setPhone($addressData['phone'] ?? null);

        return $address;
    }

    /**
     * Map Shopify status to internal status
     */
    private function mapShopifyStatus(string $shopifyStatus): string
    {
        return match ($shopifyStatus) {
            'pending' => 'pending',
            'paid' => 'processing',
            'partially_paid' => 'processing',
            'refunded' => 'cancelled',
            'partially_refunded' => 'processing',
            'voided' => 'cancelled',
            default => 'pending'
        };
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    /**
     * Update order mapping
     */
    public function updateOrderMapping(ShopifyStore $store, string $shopifyOrderId, array $orderData): bool
    {
        $mapping = $this->orderMappingRepository->findByShopifyOrderId($store, $shopifyOrderId);

        if (!$mapping) {
            return $this->syncSingleOrder($store, $shopifyOrderId, $orderData);
        }

        $mapping->setShopifyData($orderData);
        $mapping->setLastSyncAt(new \DateTime());

        // Update internal order if exists
        if ($mapping->getInternalOrderId()) {
            $this->createOrUpdateInternalOrder($store, $orderData, $mapping);
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * Cancel order
     */
    public function cancelOrder(ShopifyStore $store, string $shopifyOrderId): bool
    {
        $mapping = $this->orderMappingRepository->findByShopifyOrderId($store, $shopifyOrderId);

        if (!$mapping || !$mapping->getInternalOrderId()) {
            return false;
        }

        $order = $this->orderRepository->find($mapping->getInternalOrderId());

        if ($order) {
            $order->setStatus('cancelled');
            $this->entityManager->flush();
        }

        return true;
    }
}
