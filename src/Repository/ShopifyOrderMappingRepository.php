<?php

namespace App\Repository;

use App\Entity\ShopifyOrderMapping;
use App\Entity\ShopifyStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopifyOrderMapping>
 */
class ShopifyOrderMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopifyOrderMapping::class);
    }

    public function save(ShopifyOrderMapping $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShopifyOrderMapping $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find by Shopify order ID
     */
    public function findByShopifyOrderId(ShopifyStore $store, string $shopifyOrderId): ?ShopifyOrderMapping
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.store = :store')
            ->andWhere('m.shopifyOrderId = :orderId')
            ->setParameter('store', $store)
            ->setParameter('orderId', $shopifyOrderId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by internal order ID
     */
    public function findByInternalOrderId(int $internalOrderId): ?ShopifyOrderMapping
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.internalOrderId = :orderId')
            ->setParameter('orderId', $internalOrderId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find pending mappings
     */
    public function findPendingMappings(ShopifyStore $store, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.store = :store')
            ->andWhere('m.syncStatus = :status')
            ->setParameter('store', $store)
            ->setParameter('status', 'pending')
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count orders by sync status
     */
    public function countByStatus(ShopifyStore $store): array
    {
        $results = $this->createQueryBuilder('m')
            ->select('m.syncStatus, COUNT(m.id) as count')
            ->andWhere('m.store = :store')
            ->setParameter('store', $store)
            ->groupBy('m.syncStatus')
            ->getQuery()
            ->getResult();

        $counts = [
            'pending' => 0,
            'synced' => 0,
            'failed' => 0
        ];

        foreach ($results as $result) {
            $counts[$result['syncStatus']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * Find recent mappings
     */
    public function findRecentMappings(ShopifyStore $store, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.store = :store')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
