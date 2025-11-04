<?php

namespace App\Tests\Service\Order;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\Order\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Simplified OrderService Unit Tests
 *
 * Focuses on core state machine logic without database dependencies
 */
class OrderServiceSimpleTest extends TestCase
{
    private OrderService $orderService;
    private OrderRepository $orderRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->orderService = new OrderService(
            $this->orderRepository,
            $this->entityManager,
            $this->logger
        );
    }

    /**
     * Test valid state transitions
     *
     * @dataProvider validTransitionsProvider
     */
    public function testValidStateTransitions(string $fromStatus, string $toStatus): void
    {
        $order = new Order();
        $order->setStatus($fromStatus);
        $order->setOrderNumber('TEST-' . time());

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->orderService->updateStatus($order, $toStatus);

        $this->assertTrue($result);
        $this->assertEquals($toStatus, $order->getStatus());
    }

    public function validTransitionsProvider(): array
    {
        return [
            ['pending', 'processing'],
            ['pending', 'cancelled'],
            ['processing', 'ready_to_ship'],
            ['processing', 'cancelled'],
            ['ready_to_ship', 'shipped'],
            ['ready_to_ship', 'cancelled'],
            ['shipped', 'delivered'],
            ['shipped', 'cancelled'],
        ];
    }

    /**
     * Test invalid state transitions throw exceptions
     *
     * @dataProvider invalidTransitionsProvider
     */
    public function testInvalidStateTransitionsThrowException(string $fromStatus, string $toStatus): void
    {
        $order = new Order();
        $order->setStatus($fromStatus);
        $order->setOrderNumber('TEST-' . time());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Cannot transition/');

        $this->orderService->updateStatus($order, $toStatus);
    }

    public function invalidTransitionsProvider(): array
    {
        return [
            ['pending', 'shipped'],
            ['pending', 'delivered'],
            ['processing', 'delivered'],
            ['delivered', 'processing'],
            ['delivered', 'cancelled'],
            ['cancelled', 'processing'],
        ];
    }

    /**
     * Test order cancellation from valid states
     */
    public function testCancelOrderFromProcessing(): void
    {
        $order = new Order();
        $order->setStatus('processing');
        $order->setOrderNumber('TEST-' . time());

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->orderService->cancelOrder($order, 'Customer requested');

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $order->getStatus());
    }

    /**
     * Test cannot cancel delivered order
     */
    public function testCannotCancelDeliveredOrder(): void
    {
        $order = new Order();
        $order->setStatus('delivered');
        $order->setOrderNumber('TEST-' . time());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot cancel a delivered order');

        $this->orderService->cancelOrder($order, 'Too late');
    }

    /**
     * Test cannot cancel already cancelled order
     */
    public function testCannotCancelAlreadyCancelledOrder(): void
    {
        $order = new Order();
        $order->setStatus('cancelled');
        $order->setOrderNumber('TEST-' . time());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order is already cancelled');

        $this->orderService->cancelOrder($order, 'Duplicate');
    }

    /**
     * Test statistics call delegates to repository
     */
    public function testGetStatisticsDelegatesToRepository(): void
    {
        $user = $this->createMock(User::class);

        $this->orderRepository->expects($this->once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(15);

        $this->orderRepository->expects($this->atLeastOnce())
            ->method('countByUserAndDateRange')
            ->willReturn(5);

        $this->orderRepository->expects($this->atLeastOnce())
            ->method('countByUserAndStatus')
            ->willReturn(3);

        $stats = $this->orderService->getStatisticsByUser($user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(15, $stats['total']);
    }
}
