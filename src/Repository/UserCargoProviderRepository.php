<?php

namespace App\Repository;

use App\Entity\UserCargoProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserCargoProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCargoProvider::class);
    }

    public function findByUser($user, bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('ucp')
            ->where('ucp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ucp.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('ucp.isActive = true');
        }

        return $qb->getQuery()->getResult();
    }

    public function findActiveByUser($user): array
    {
        return $this->findByUser($user, true);
    }

    public function findByCode(string $code): ?UserCargoProvider
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function countByUser($user): int
    {
        return $this->createQueryBuilder('ucp')
            ->select('COUNT(ucp.id)')
            ->where('ucp.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
