<?php

namespace App\Repository;

use App\Entity\CargoProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CargoProvider>
 */
class CargoProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CargoProvider::class);
    }

    /**
     * Find all active cargo providers ordered by priority
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('cp')
            ->where('cp.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('cp.priority', 'DESC')
            ->addOrderBy('cp.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find provider by code
     */
    public function findByCode(string $code): ?CargoProvider
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Get all providers with user config count
     */
    public function findAllWithStats(): array
    {
        return $this->createQueryBuilder('cp')
            ->leftJoin('cp.userConfigs', 'uc')
            ->addSelect('COUNT(uc.id) as configCount')
            ->groupBy('cp.id')
            ->orderBy('cp.priority', 'DESC')
            ->addOrderBy('cp.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
