<?php

namespace App\Repository;

use App\Entity\CargoProviderConfig;
use App\Entity\User;
use App\Entity\CargoProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CargoProviderConfig>
 */
class CargoProviderConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CargoProviderConfig::class);
    }

    /**
     * Find user's active cargo configurations
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('cpc')
            ->innerJoin('cpc.provider', 'p')
            ->where('cpc.user = :user')
            ->andWhere('cpc.isActive = :active')
            ->andWhere('p.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('p.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find user's configuration for specific provider
     */
    public function findByUserAndProvider(User $user, CargoProvider $provider): ?CargoProviderConfig
    {
        return $this->findOneBy([
            'user' => $user,
            'provider' => $provider
        ]);
    }

    /**
     * Get all configurations for a user with provider details
     */
    public function findAllByUserWithProvider(User $user): array
    {
        return $this->createQueryBuilder('cpc')
            ->innerJoin('cpc.provider', 'p')
            ->addSelect('p')
            ->where('cpc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
