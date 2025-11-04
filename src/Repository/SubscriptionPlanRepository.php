<?php

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('sp.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFreePlan(): ?SubscriptionPlan
    {
        return $this->findByCode('free');
    }
}
