<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Shop;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByShop(Shop $shop, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.shop = :shop')
            ->setParameter('shop', $shop);

        if (isset($filters['status'])) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $qb->andWhere('o.paymentMethod = :paymentMethod')
               ->setParameter('paymentMethod', $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $qb->andWhere('o.orderDate >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('o.orderDate <= :dateTo')
               ->setParameter('dateTo', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $qb->andWhere('o.orderNumber LIKE :search OR o.customerName LIKE :search OR o.customerEmail LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByShop(Shop $shop, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.shop = :shop')
            ->setParameter('shop', $shop);

        if (isset($filters['status'])) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['payment_method'])) {
            $qb->andWhere('o.paymentMethod = :paymentMethod')
               ->setParameter('paymentMethod', $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $qb->andWhere('o.orderDate >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('o.orderDate <= :dateTo')
               ->setParameter('dateTo', $filters['date_to']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.orderNumber = :orderNumber')
            ->setParameter('orderNumber', $orderNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findReadyToShip(Shop $shop): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.shop = :shop')
            ->andWhere('o.status = :status')
            ->setParameter('shop', $shop)
            ->setParameter('status', 'ready_to_ship')
            ->orderBy('o.orderDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatsByShop(Shop $shop, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('
                COUNT(o.id) as total_orders,
                SUM(o.totalAmount) as total_revenue,
                AVG(o.totalAmount) as avg_order_value,
                SUM(CASE WHEN o.status = :shipped THEN 1 ELSE 0 END) as shipped_count,
                SUM(CASE WHEN o.status = :delivered THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN o.paymentMethod = :cod_cash THEN 1 ELSE 0 END) as cod_cash_count,
                SUM(CASE WHEN o.paymentMethod = :cod_card THEN 1 ELSE 0 END) as cod_card_count,
                SUM(CASE WHEN o.paymentMethod = :online THEN 1 ELSE 0 END) as online_count
            ')
            ->where('o.shop = :shop')
            ->andWhere('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('shop', $shop)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('shipped', 'shipped')
            ->setParameter('delivered', 'delivered')
            ->setParameter('cod_cash', 'cod_cash')
            ->setParameter('cod_card', 'cod_credit_card')
            ->setParameter('online', 'online');

        return $qb->getQuery()->getSingleResult();
    }

    public function getTodayOrderCount(Shop $shop): int
    {
        $today = new \DateTime('today');
        
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.shop = :shop')
            ->andWhere('o.orderDate >= :today')
            ->setParameter('shop', $shop)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getMonthlyOrderCount(Shop $shop, int $year, int $month): int
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.shop = :shop')
            ->andWhere('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('shop', $shop)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Dashboard statistics methods
    public function countByUser($user): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserAndDateRange($user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->andWhere('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumRevenueByUserAndDateRange($user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->andWhere('o.orderDate BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    public function countByUserAndStatus($user, string $status): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRecentByUser($user, int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyOrderStats($user, int $months = 6): array
    {
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$months} months");

        $results = $this->createQueryBuilder('o')
            ->select('SUBSTRING(o.orderDate, 1, 7) as month, COUNT(o.id) as count')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->andWhere('o.orderDate >= :startDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function getOrderCountByStatus($user): array
    {
        $results = $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function findByShops(array $shops, int $limit = 50, int $offset = 0): array
    {
        if (empty($shops)) {
            return [];
        }

        return $this->createQueryBuilder('o')
            ->where('o.shop IN (:shops)')
            ->setParameter('shops', $shops)
            ->orderBy('o.orderDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByShops(array $shops): int
    {
        if (empty($shops)) {
            return 0;
        }

        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.shop IN (:shops)')
            ->setParameter('shops', $shops)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByUser($user, array $filters = [], int $limit = 50, int $start = 0): array
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user);

        // Apply filters
        if (!empty($filters['status'])) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['shop_id'])) {
            $qb->andWhere('o.shop = :shop')
               ->setParameter('shop', $filters['shop_id']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('(o.orderNumber LIKE :search OR o.customerName LIKE :search OR o.customerEmail LIKE :search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('o.orderDate >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('o.orderDate <= :dateTo')
               ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        if (!empty($filters['min_amount'])) {
            $qb->andWhere('o.totalAmount >= :minAmount')
               ->setParameter('minAmount', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $qb->andWhere('o.totalAmount <= :maxAmount')
               ->setParameter('maxAmount', $filters['max_amount']);
        }

        // Sorting
        $orderBy = $filters['order_by'] ?? 'orderDate';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $qb->orderBy('o.' . $orderBy, $orderDir);

        return $qb->setMaxResults($limit)
            ->setFirstResult($start)
            ->getQuery()
            ->getResult();
    }

    public function countByUserFiltered($user, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user);

        // Apply same filters
        if (!empty($filters['status'])) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['shop_id'])) {
            $qb->andWhere('o.shop = :shop')
               ->setParameter('shop', $filters['shop_id']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('(o.orderNumber LIKE :search OR o.customerName LIKE :search OR o.customerEmail LIKE :search)')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('o.orderDate >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('o.orderDate <= :dateTo')
               ->setParameter('dateTo', new \DateTime($filters['date_to'] . ' 23:59:59'));
        }

        if (!empty($filters['min_amount'])) {
            $qb->andWhere('o.totalAmount >= :minAmount')
               ->setParameter('minAmount', $filters['min_amount']);
        }

        if (!empty($filters['max_amount'])) {
            $qb->andWhere('o.totalAmount <= :maxAmount')
               ->setParameter('maxAmount', $filters['max_amount']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getStatisticsByUser($user): array
    {
        $totalOrders = $this->countByUser($user);

        $totalRevenue = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->andWhere('o.status NOT IN (:excludedStatuses)')
            ->setParameter('user', $user)
            ->setParameter('excludedStatuses', ['cancelled', 'refunded'])
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $statusCounts = $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        $last30Days = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->join('o.shop', 's')
            ->where('s.user = :user')
            ->andWhere('o.orderDate >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTime('-30 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'status_counts' => $statusCounts,
            'last_30_days' => $last30Days,
        ];
    }
}
