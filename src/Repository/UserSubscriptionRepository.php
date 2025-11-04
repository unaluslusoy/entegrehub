<?php

namespace App\Repository;

use App\Entity\UserSubscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSubscription::class);
    }

    public function findActiveByUser(User $user): ?UserSubscription
    {
        return $this->createQueryBuilder('us')
            ->where('us.user = :user')
            ->andWhere('us.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->orderBy('us.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiringSoon(int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify("+{$days} days");

        return $this->createQueryBuilder('us')
            ->where('us.status = :status')
            ->andWhere('us.endDate <= :date')
            ->andWhere('us.endDate >= :now')
            ->setParameter('status', 'active')
            ->setParameter('date', $date)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findExpired(): array
    {
        return $this->createQueryBuilder('us')
            ->where('us.status = :status')
            ->andWhere('us.endDate < :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function resetMonthlyUsageForAll(): int
    {
        return $this->createQueryBuilder('us')
            ->update()
            ->set('us.currentMonthOrders', 0)
            ->set('us.currentMonthSms', 0)
            ->set('us.currentMonthEmails', 0)
            ->where('us.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->execute();
    }

    /**
     * Get total monthly revenue from active subscriptions
     */
    public function getMonthlyRevenue(): float
    {
        $result = $this->createQueryBuilder('us')
            ->select('SUM(CASE 
                WHEN us.billingPeriod = :monthly THEN sp.monthlyPrice 
                WHEN us.billingPeriod = :yearly THEN sp.yearlyPrice / 12 
                ELSE 0 
            END) as total')
            ->join('us.plan', 'sp')
            ->where('us.status = :status')
            ->setParameter('status', 'active')
            ->setParameter('monthly', 'monthly')
            ->setParameter('yearly', 'yearly')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}

