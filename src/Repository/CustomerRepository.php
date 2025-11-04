<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function save(Customer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Customer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active customers
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find customers with active subscriptions
     */
    public function findWithActiveSubscriptions(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->andWhere('c.subscriptionEndDate > :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('c.subscriptionEndDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find customers with expiring subscriptions
     */
    public function findWithExpiringSubscriptions(int $days = 30): array
    {
        $now = new \DateTime();
        $endDate = (clone $now)->modify("+{$days} days");

        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->andWhere('c.subscriptionEndDate BETWEEN :now AND :endDate')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('endDate', $endDate)
            ->orderBy('c.subscriptionEndDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(): array
    {
        $total = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $active = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $withSubscription = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.subscriptionEndDate > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'with_subscription' => $withSubscription,
        ];
    }
}
