<?php

namespace App\Repository;

use App\Entity\ShopifyStore;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopifyStore>
 */
class ShopifyStoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopifyStore::class);
    }

    public function save(ShopifyStore $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShopifyStore $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active stores by user
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find store by shop domain
     */
    public function findByShopDomain(string $shopDomain): ?ShopifyStore
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.shopDomain = :domain')
            ->setParameter('domain', $shopDomain)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find stores that need sync
     */
    public function findStoresNeedingSync(int $hoursAgo = 1): array
    {
        $dateThreshold = new \DateTime("-{$hoursAgo} hours");
        
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.lastOrderSyncAt < :threshold OR s.lastOrderSyncAt IS NULL')
            ->setParameter('active', true)
            ->setParameter('threshold', $dateThreshold)
            ->orderBy('s.lastOrderSyncAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sync statistics
     */
    public function getSyncStatistics(User $user): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('
                COUNT(s.id) as totalStores,
                SUM(s.totalOrdersSynced) as totalOrders,
                AVG(s.syncProgress) as avgProgress
            ')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalStores' => (int) $result['totalStores'],
            'totalOrders' => (int) $result['totalOrders'],
            'avgProgress' => round((float) $result['avgProgress'], 2)
        ];
    }

    /**
     * Find by user and shop domain
     */
    public function findByUserAndDomain(User $user, string $shopDomain): ?ShopifyStore
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.shopDomain = :domain')
            ->setParameter('user', $user)
            ->setParameter('domain', $shopDomain)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
