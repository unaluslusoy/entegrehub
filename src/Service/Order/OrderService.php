<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * OrderService - Centralized business logic for Order management
 *
 * This service handles all order-related operations and business rules.
 * Moved from controllers to follow Single Responsibility Principle.
 */
class OrderService
{
    // Valid order statuses and their allowed transitions
    private const STATUS_TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['ready_to_ship', 'cancelled'],
        'ready_to_ship' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Update order status with validation
     */
    public function updateStatus(Order $order, string $newStatus, ?string $note = null): bool
    {
        $currentStatus = $order->getStatus();

        // Validate status transition
        if (!$this->canTransitionTo($currentStatus, $newStatus)) {
            $this->logger->warning('Invalid order status transition', [
                'order_id' => $order->getId(),
                'from' => $currentStatus,
                'to' => $newStatus,
            ]);

            throw new \InvalidArgumentException(
                sprintf('Cannot transition from "%s" to "%s"', $currentStatus, $newStatus)
            );
        }

        $oldStatus = $order->getStatus();
        $order->setStatus($newStatus);

        if ($note) {
            $this->addNote($order, $note);
        }

        $this->entityManager->flush();

        $this->logger->info('Order status updated', [
            'order_id' => $order->getId(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
        ]);

        return true;
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order, string $reason): bool
    {
        if ($order->getStatus() === 'delivered') {
            throw new \InvalidArgumentException('Cannot cancel a delivered order');
        }

        if ($order->getStatus() === 'cancelled') {
            throw new \InvalidArgumentException('Order is already cancelled');
        }

        $order->setStatus('cancelled');
        $this->addNote($order, 'Order cancelled. Reason: ' . $reason);

        $this->logger->info('Order cancelled', [
            'order_id' => $order->getId(),
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Add a note to an order
     */
    public function addNote(Order $order, string $note): void
    {
        $notes = $order->getNotes() ?? [];
        $notes[] = [
            'text' => $note,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        $order->setNotes($notes);

        $this->entityManager->flush();
    }

    /**
     * Get order statistics for a user
     */
    public function getStatisticsByUser(User $user): array
    {
        $now = new \DateTime();
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $startOfToday = new \DateTime('today 00:00:00');

        return [
            'total' => $this->orderRepository->countByUser($user),
            'this_month' => $this->orderRepository->countByUserAndDateRange($user, $startOfMonth, $now),
            'today' => $this->orderRepository->countByUserAndDateRange($user, $startOfToday, $now),
            'pending' => $this->orderRepository->countByUserAndStatus($user, 'pending'),
            'processing' => $this->orderRepository->countByUserAndStatus($user, 'processing'),
            'shipped' => $this->orderRepository->countByUserAndStatus($user, 'shipped'),
            'delivered' => $this->orderRepository->countByUserAndStatus($user, 'delivered'),
            'cancelled' => $this->orderRepository->countByUserAndStatus($user, 'cancelled'),
            'revenue_this_month' => $this->orderRepository->sumRevenueByUserAndDateRange($user, $startOfMonth, $now),
        ];
    }

    /**
     * Validate ownership - check if user owns the order
     */
    public function validateOwnership(Order $order, User $user): bool
    {
        return $order->getShop() && $order->getShop()->getUser() === $user;
    }

    /**
     * Check if status transition is valid
     */
    private function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        if (!isset(self::STATUS_TRANSITIONS[$currentStatus])) {
            return false;
        }

        return in_array($newStatus, self::STATUS_TRANSITIONS[$currentStatus]);
    }

    /**
     * Get available status transitions for current status
     */
    public function getAvailableTransitions(string $currentStatus): array
    {
        return self::STATUS_TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Bulk update status for multiple orders
     */
    public function bulkUpdateStatus(array $orderIds, string $newStatus, User $user): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($orderIds as $orderId) {
            try {
                $order = $this->orderRepository->find($orderId);

                if (!$order) {
                    $results['errors'][] = "Order #{$orderId} not found";
                    $results['failed']++;
                    continue;
                }

                if (!$this->validateOwnership($order, $user)) {
                    $results['errors'][] = "Order #{$orderId} - Access denied";
                    $results['failed']++;
                    continue;
                }

                $this->updateStatus($order, $newStatus);
                $results['success']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Order #{$orderId} - " . $e->getMessage();
                $results['failed']++;

                $this->logger->error('Bulk update failed for order', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
