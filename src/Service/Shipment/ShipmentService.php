<?php

namespace App\Service\Shipment;

use App\Entity\Shipment;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\CargoCompany;
use App\Repository\ShipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * ShipmentService - Centralized business logic for Shipment management
 *
 * This service consolidates duplicate shipment logic from Admin and User controllers.
 */
class ShipmentService
{
    // Valid shipment statuses and their allowed transitions
    private const STATUS_TRANSITIONS = [
        'created' => ['picked_up', 'cancelled'],
        'picked_up' => ['in_transit', 'cancelled'],
        'in_transit' => ['out_for_delivery', 'cancelled', 'returned'],
        'out_for_delivery' => ['delivered', 'returned'],
        'delivered' => [],
        'returned' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly ShipmentRepository $shipmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create a new shipment
     */
    public function createShipment(Order $order, CargoCompany $cargoCompany, array $data): Shipment
    {
        $shipment = new Shipment();
        $shipment->setOrder($order);
        $shipment->setCargoCompany($cargoCompany);
        $shipment->setTrackingNumber($this->generateTrackingNumber());
        $shipment->setStatus('created');

        // Set shipment details from data
        if (isset($data['service_type'])) {
            $shipment->setServiceType($data['service_type']);
        }

        if (isset($data['weight'])) {
            $shipment->setWeight((float) $data['weight']);
        }

        if (isset($data['desi'])) {
            $shipment->setDesi((float) $data['desi']);
        }

        if (isset($data['cod_amount'])) {
            $shipment->setCodAmount((float) $data['cod_amount']);
        }

        if (isset($data['note'])) {
            $shipment->setNotes($data['note']);
        }

        $this->entityManager->persist($shipment);
        $this->entityManager->flush();

        $this->logger->info('Shipment created', [
            'shipment_id' => $shipment->getId(),
            'order_id' => $order->getId(),
            'cargo_company' => $cargoCompany->getCode(),
            'tracking_number' => $shipment->getTrackingNumber(),
        ]);

        // Update order status if needed
        if ($order->getStatus() === 'ready_to_ship') {
            $order->setStatus('shipped');
            $this->entityManager->flush();
        }

        return $shipment;
    }

    /**
     * Update shipment status with automatic order status sync
     */
    public function updateStatus(Shipment $shipment, string $newStatus, ?string $note = null): bool
    {
        $currentStatus = $shipment->getStatus();

        // Validate status transition
        if (!$this->canTransitionTo($currentStatus, $newStatus)) {
            $this->logger->warning('Invalid shipment status transition', [
                'shipment_id' => $shipment->getId(),
                'from' => $currentStatus,
                'to' => $newStatus,
            ]);

            throw new \InvalidArgumentException(
                sprintf('Cannot transition from "%s" to "%s"', $currentStatus, $newStatus)
            );
        }

        $oldStatus = $shipment->getStatus();
        $shipment->setStatus($newStatus);

        if ($note) {
            $shipment->setNotes(($shipment->getNotes() ?? '') . "\n" . $note);
        }

        // Update tracking history
        $history = $shipment->getTrackingHistory() ?? [];
        $history[] = [
            'status' => $newStatus,
            'note' => $note,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        $shipment->setTrackingHistory($history);

        // Set delivery date if delivered
        if ($newStatus === 'delivered' && !$shipment->getDeliveredAt()) {
            $shipment->setDeliveredAt(new \DateTime());
        }

        $this->entityManager->flush();

        // Sync order status
        $this->syncOrderStatus($shipment);

        $this->logger->info('Shipment status updated', [
            'shipment_id' => $shipment->getId(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
        ]);

        return true;
    }

    /**
     * Cancel a shipment
     */
    public function cancelShipment(Shipment $shipment, string $reason): bool
    {
        if ($shipment->getStatus() === 'delivered') {
            throw new \InvalidArgumentException('Cannot cancel a delivered shipment');
        }

        if ($shipment->getStatus() === 'cancelled') {
            throw new \InvalidArgumentException('Shipment is already cancelled');
        }

        $this->updateStatus($shipment, 'cancelled', 'Cancelled: ' . $reason);

        return true;
    }

    /**
     * Generate a unique tracking number
     */
    public function generateTrackingNumber(): string
    {
        $prefix = 'KRG';
        $timestamp = time();
        $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return sprintf('%s%s%s', $prefix, $timestamp, $random);
    }

    /**
     * Sync order status based on shipment status
     */
    private function syncOrderStatus(Shipment $shipment): void
    {
        $order = $shipment->getOrder();
        if (!$order) {
            return;
        }

        $shipmentStatus = $shipment->getStatus();

        // Map shipment status to order status
        $statusMap = [
            'picked_up' => 'shipped',
            'in_transit' => 'shipped',
            'out_for_delivery' => 'shipped',
            'delivered' => 'delivered',
            'returned' => 'cancelled',
            'cancelled' => 'cancelled',
        ];

        if (isset($statusMap[$shipmentStatus]) && $order->getStatus() !== 'delivered') {
            $order->setStatus($statusMap[$shipmentStatus]);
            $this->entityManager->flush();

            $this->logger->info('Order status synced from shipment', [
                'order_id' => $order->getId(),
                'new_status' => $statusMap[$shipmentStatus],
                'shipment_status' => $shipmentStatus,
            ]);
        }
    }

    /**
     * Get shipment statistics for a user
     */
    public function getStatisticsByUser(User $user): array
    {
        $now = new \DateTime();
        $startOfMonth = new \DateTime('first day of this month 00:00:00');

        return [
            'total' => $this->shipmentRepository->countByUser($user),
            'this_month' => $this->shipmentRepository->countByUserAndDateRange($user, $startOfMonth, $now),
            'active' => $this->shipmentRepository->countByUserAndStatuses(
                $user,
                ['created', 'picked_up', 'in_transit', 'out_for_delivery']
            ),
            'delivered_this_month' => $this->shipmentRepository->countByUserStatusAndDateRange(
                $user,
                'delivered',
                $startOfMonth,
                $now
            ),
        ];
    }

    /**
     * Validate ownership - check if user owns the shipment
     */
    public function validateOwnership(Shipment $shipment, User $user): bool
    {
        $order = $shipment->getOrder();
        return $order && $order->getShop() && $order->getShop()->getUser() === $user;
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
     * Bulk update status for multiple shipments
     */
    public function bulkUpdateStatus(array $shipmentIds, string $newStatus, User $user): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($shipmentIds as $shipmentId) {
            try {
                $shipment = $this->shipmentRepository->find($shipmentId);

                if (!$shipment) {
                    $results['errors'][] = "Shipment #{$shipmentId} not found";
                    $results['failed']++;
                    continue;
                }

                if (!$this->validateOwnership($shipment, $user)) {
                    $results['errors'][] = "Shipment #{$shipmentId} - Access denied";
                    $results['failed']++;
                    continue;
                }

                $this->updateStatus($shipment, $newStatus);
                $results['success']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Shipment #{$shipmentId} - " . $e->getMessage();
                $results['failed']++;

                $this->logger->error('Bulk update failed for shipment', [
                    'shipment_id' => $shipmentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
