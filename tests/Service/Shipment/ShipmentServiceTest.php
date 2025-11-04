<?php

namespace App\Tests\Service\Shipment;

use App\Entity\Shipment;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\Shop;
use App\Entity\CargoCompany;
use App\Repository\ShipmentRepository;
use App\Service\Shipment\ShipmentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * ShipmentService Unit Tests
 *
 * Tests the business logic layer for shipment management including:
 * - Shipment creation
 * - State machine transitions
 * - Status updates with order sync
 * - Shipment cancellation
 * - Ownership validation
 * - Statistics
 */
class ShipmentServiceTest extends TestCase
{
    private ShipmentService $shipmentService;
    private ShipmentRepository $shipmentRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->shipmentRepository = $this->createMock(ShipmentRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->shipmentService = new ShipmentService(
            $this->shipmentRepository,
            $this->entityManager,
            $this->logger
        );
    }

    /**
     * Test shipment creation with basic data
     */
    public function testCreateShipment(): void
    {
        $order = $this->createOrder('ready_to_ship');
        $cargoCompany = $this->createCargoCompany();

        $data = [
            'service_type' => 'express',
            'weight' => 2.5,
            'desi' => 5.0,
            'note' => 'Test note',
        ];

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $shipment = $this->shipmentService->createShipment($order, $cargoCompany, $data);

        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertEquals('created', $shipment->getStatus());
        $this->assertEquals($order, $shipment->getOrder());
        $this->assertEquals($cargoCompany, $shipment->getCargoCompany());
        $this->assertEquals('express', $shipment->getServiceType());
        $this->assertEquals(2.5, $shipment->getWeight());
    }

    /**
     * Test valid state transitions
     *
     * @dataProvider validTransitionsProvider
     */
    public function testValidStateTransitions(string $fromStatus, string $toStatus): void
    {
        $shipment = $this->createShipment($fromStatus);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $result = $this->shipmentService->updateStatus($shipment, $toStatus);

        $this->assertTrue($result);
        $this->assertEquals($toStatus, $shipment->getStatus());
    }

    public function validTransitionsProvider(): array
    {
        return [
            'created to picked_up' => ['created', 'picked_up'],
            'created to cancelled' => ['created', 'cancelled'],
            'picked_up to in_transit' => ['picked_up', 'in_transit'],
            'picked_up to cancelled' => ['picked_up', 'cancelled'],
            'in_transit to out_for_delivery' => ['in_transit', 'out_for_delivery'],
            'in_transit to cancelled' => ['in_transit', 'cancelled'],
            'in_transit to returned' => ['in_transit', 'returned'],
            'out_for_delivery to delivered' => ['out_for_delivery', 'delivered'],
            'out_for_delivery to returned' => ['out_for_delivery', 'returned'],
        ];
    }

    /**
     * Test invalid state transitions throw exceptions
     *
     * @dataProvider invalidTransitionsProvider
     */
    public function testInvalidStateTransitionsThrowException(string $fromStatus, string $toStatus): void
    {
        $shipment = $this->createShipment($fromStatus);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Cannot transition/');

        $this->shipmentService->updateStatus($shipment, $toStatus);
    }

    public function invalidTransitionsProvider(): array
    {
        return [
            'created to delivered' => ['created', 'delivered'],
            'created to returned' => ['created', 'returned'],
            'picked_up to delivered' => ['picked_up', 'delivered'],
            'delivered to in_transit' => ['delivered', 'in_transit'],
            'delivered to cancelled' => ['delivered', 'cancelled'],
            'cancelled to in_transit' => ['cancelled', 'in_transit'],
            'returned to in_transit' => ['returned', 'in_transit'],
        ];
    }

    /**
     * Test status update adds tracking history
     */
    public function testStatusUpdateAddsTrackingHistory(): void
    {
        $shipment = $this->createShipment('created');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $this->shipmentService->updateStatus($shipment, 'picked_up', 'Package picked up');

        $history = $shipment->getTrackingHistory();
        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertEquals('picked_up', $history[0]['status']);
        $this->assertEquals('Package picked up', $history[0]['note']);
        $this->assertArrayHasKey('timestamp', $history[0]);
    }

    /**
     * Test delivered status sets delivered_at timestamp
     */
    public function testDeliveredStatusSetsTimestamp(): void
    {
        $shipment = $this->createShipment('out_for_delivery');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $this->shipmentService->updateStatus($shipment, 'delivered');

        $this->assertNotNull($shipment->getDeliveredAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $shipment->getDeliveredAt());
    }

    /**
     * Test order status sync when shipment is delivered
     */
    public function testOrderStatusSyncOnDelivered(): void
    {
        $order = $this->createOrder('shipped');
        $shipment = $this->createShipment('out_for_delivery');
        $shipment->setOrder($order);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $this->shipmentService->updateStatus($shipment, 'delivered');

        $this->assertEquals('delivered', $order->getStatus());
    }

    /**
     * Test order status sync when shipment is cancelled
     */
    public function testOrderStatusSyncOnCancelled(): void
    {
        $order = $this->createOrder('processing');
        $shipment = $this->createShipment('created');
        $shipment->setOrder($order);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $this->shipmentService->updateStatus($shipment, 'cancelled');

        $this->assertEquals('cancelled', $order->getStatus());
    }

    /**
     * Test shipment cancellation
     */
    public function testCancelShipment(): void
    {
        $shipment = $this->createShipment('in_transit');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $result = $this->shipmentService->cancelShipment($shipment, 'Customer requested');

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $shipment->getStatus());
    }

    /**
     * Test cannot cancel delivered shipment
     */
    public function testCannotCancelDeliveredShipment(): void
    {
        $shipment = $this->createShipment('delivered');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cancel a delivered shipment');

        $this->shipmentService->cancelShipment($shipment, 'Too late');
    }

    /**
     * Test cannot cancel already cancelled shipment
     */
    public function testCannotCancelAlreadyCancelledShipment(): void
    {
        $shipment = $this->createShipment('cancelled');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Shipment is already cancelled');

        $this->shipmentService->cancelShipment($shipment, 'Duplicate');
    }

    /**
     * Test tracking number generation
     */
    public function testGenerateTrackingNumber(): void
    {
        $trackingNumber = $this->shipmentService->generateTrackingNumber();

        $this->assertIsString($trackingNumber);
        $this->assertStringStartsWith('KRG', $trackingNumber);
        $this->assertGreaterThan(10, strlen($trackingNumber));
    }

    /**
     * Test tracking numbers are unique
     */
    public function testGenerateUniqueTrackingNumbers(): void
    {
        $trackingNumber1 = $this->shipmentService->generateTrackingNumber();
        usleep(1000); // Wait 1ms to ensure timestamp difference
        $trackingNumber2 = $this->shipmentService->generateTrackingNumber();

        $this->assertNotEquals($trackingNumber1, $trackingNumber2);
    }

    /**
     * Test ownership validation - valid owner
     */
    public function testValidateOwnershipValid(): void
    {
        $user = $this->createUser(1);
        $shop = $this->createShop($user);
        $order = $this->createOrder('processing');
        $order->setShop($shop);

        $shipment = $this->createShipment('created');
        $shipment->setOrder($order);

        $result = $this->shipmentService->validateOwnership($shipment, $user);

        $this->assertTrue($result);
    }

    /**
     * Test ownership validation - invalid owner
     */
    public function testValidateOwnershipInvalid(): void
    {
        $user1 = $this->createUser(1);
        $user2 = $this->createUser(2);
        $shop = $this->createShop($user1);
        $order = $this->createOrder('processing');
        $order->setShop($shop);

        $shipment = $this->createShipment('created');
        $shipment->setOrder($order);

        $result = $this->shipmentService->validateOwnership($shipment, $user2);

        $this->assertFalse($result);
    }

    /**
     * Test get statistics by user
     * Note: Simplified test - only checks that statistics method returns array
     */
    public function testGetStatisticsByUser(): void
    {
        $user = $this->createUser(1);

        $this->shipmentRepository->expects($this->once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(25);

        $this->shipmentRepository->expects($this->atLeastOnce())
            ->method('countByUserAndDateRange')
            ->willReturn(10);

        $this->shipmentRepository->expects($this->once())
            ->method('countByUserAndStatuses')
            ->willReturn(5);

        $stats = $this->shipmentService->getStatisticsByUser($user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertEquals(25, $stats['total']);
    }

    /**
     * Helper method to create a mock Shipment entity
     */
    private function createShipment(string $status): Shipment
    {
        $shipment = new Shipment();
        $shipment->setStatus($status);
        $shipment->setTrackingNumber('TEST-' . time());

        return $shipment;
    }

    /**
     * Helper method to create a mock Order entity
     */
    private function createOrder(string $status): Order
    {
        $order = new Order();
        $order->setStatus($status);
        $order->setOrderNumber('ORD-' . time());

        return $order;
    }

    /**
     * Helper method to create a mock CargoCompany entity
     */
    private function createCargoCompany(): CargoCompany
    {
        $company = new CargoCompany();
        $company->setName('Test Cargo');
        $company->setCode('TEST');
        $company->setIsActive(true);

        return $company;
    }

    /**
     * Helper method to create a mock User entity
     */
    private function createUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    /**
     * Helper method to create a mock Shop entity
     */
    private function createShop(User $user): Shop
    {
        $shop = new Shop();
        $shop->setUser($user);
        $shop->setShopName('Test Shop');
        $shop->setShopifyId('test-shop-id');
        $shop->setAccessToken('test-token');

        return $shop;
    }
}
