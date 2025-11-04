<?php

namespace App\Repository;

use App\Entity\Shipment;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shipment::class);
    }

    public function findByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return $this->createQueryBuilder('s')
            ->where('s.trackingNumber = :trackingNumber')
            ->setParameter('trackingNumber', $trackingNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByOrder(Order $order): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.order = :order')
            ->setParameter('order', $order)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveShipments(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status NOT IN (:statuses)')
            ->setParameter('statuses', ['delivered', 'cancelled', 'returned'])
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNeedingTrackingUpdate(): array
    {
        $date = new \DateTime();
        $date->modify('-30 minutes');

        return $this->createQueryBuilder('s')
            ->where('s.status NOT IN (:statuses)')
            ->andWhere('s.lastTrackedAt IS NULL OR s.lastTrackedAt < :date')
            ->setParameter('statuses', ['delivered', 'cancelled', 'returned'])
            ->setParameter('date', $date)
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    // Dashboard statistics methods
    public function findRecentByUser($user, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUserAndStatuses($user, array $statuses): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getShipmentCountByStatus($user): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as count')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user)
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function countByUser($user): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserAndDateRange($user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumCostByUserAndDateRange($user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): float
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(COALESCE(s.actualCost, s.estimatedCost, 0))')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    public function countByUserAndStatus($user, string $status): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getMonthlyShipmentStats($user, int $months = 6): array
    {
        $endDate = new \DateTime();
        $startDate = (clone $endDate)->modify("-{$months} months");

        $results = $this->createQueryBuilder('s')
            ->select('SUBSTRING(s.createdAt, 1, 7) as month, COUNT(s.id) as count, SUM(COALESCE(s.actualCost, s.estimatedCost, 0)) as total_cost')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.createdAt >= :startDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function getDeliverySuccessRate($user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('
                COUNT(s.id) as total,
                SUM(CASE WHEN s.status = :delivered THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN s.status = :cancelled THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN s.status = :returned THEN 1 ELSE 0 END) as returned
            ')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('delivered', 'delivered')
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('returned', 'returned')
            ->getQuery()
            ->getSingleResult();

        return $results;
    }

    public function getCargoCompanyPerformance($user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('
                c.name as cargo_name,
                COUNT(s.id) as total_shipments,
                SUM(CASE WHEN s.status = :delivered THEN 1 ELSE 0 END) as delivered_count,
                SUM(COALESCE(s.actualCost, s.estimatedCost, 0)) as total_cost,
                AVG(TIMESTAMPDIFF(DAY, s.createdAt, s.deliveredAt)) as avg_delivery_days
            ')
            ->join('s.cargoCompany', 'c')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('delivered', 'delivered')
            ->groupBy('c.id')
            ->orderBy('total_shipments', 'DESC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function findByFilters($user, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user);

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['cargo_company_id'])) {
            $qb->andWhere('s.cargoCompany = :cargoCompany')
               ->setParameter('cargoCompany', $filters['cargo_company_id']);
        }

        if (isset($filters['date_from'])) {
            $qb->andWhere('s.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('s.createdAt <= :dateTo')
               ->setParameter('dateTo', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $qb->andWhere('s.trackingNumber LIKE :search OR o.orderNumber LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByFilters($user, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user);

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['cargo_company_id'])) {
            $qb->andWhere('s.cargoCompany = :cargoCompany')
               ->setParameter('cargoCompany', $filters['cargo_company_id']);
        }

        if (isset($filters['date_from'])) {
            $qb->andWhere('s.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $qb->andWhere('s.createdAt <= :dateTo')
               ->setParameter('dateTo', $filters['date_to']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getStatisticsByUser($user): array
    {
        $totalShipments = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $activeShipments = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.status NOT IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['delivered', 'cancelled', 'returned'])
            ->getQuery()
            ->getSingleScalarResult();

        $statusCounts = $this->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as count')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->setParameter('user', $user)
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        $last30Days = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.order', 'o')
            ->join('o.shop', 'sh')
            ->where('sh.user = :user')
            ->andWhere('s.createdAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', new \DateTime('-30 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_shipments' => $totalShipments,
            'active_shipments' => $activeShipments,
            'status_counts' => $statusCounts,
            'last_30_days' => $last30Days,
        ];
    }
}


