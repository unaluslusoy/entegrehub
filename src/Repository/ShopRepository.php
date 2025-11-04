<?php

namespace App\Repository;

use App\Entity\Shop;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shop>
 */
class ShopRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shop::class);
    }

    public function save(Shop $shop, bool $flush = false): void
    {
        $this->getEntityManager()->persist($shop);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Shop $shop, bool $flush = false): void
    {
        $this->getEntityManager()->remove($shop);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDomain(string $domain): ?Shop
    {
        return $this->createQueryBuilder('s')
            ->where('s.shopDomain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNeedingSync(): array
    {
        $date = new \DateTime();
        $date->modify('-1 hour');

        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->andWhere('s.autoSync = :autoSync')
            ->andWhere('s.lastSyncAt IS NULL OR s.lastSyncAt < :date')
            ->setParameter('active', true)
            ->setParameter('autoSync', true)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUserAndActive(User $user, bool $isActive): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->andWhere('s.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', $isActive)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
