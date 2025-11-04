<?php

namespace App\Repository;

use App\Entity\ShopifySyncLog;
use App\Entity\ShopifyStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopifySyncLog>
 */
class ShopifySyncLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopifySyncLog::class);
    }

    public function save(ShopifySyncLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShopifySyncLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find recent logs by store
     */
    public function findRecentByStore(ShopifyStore $store, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.store = :store')
            ->setParameter('store', $store)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by store and type
     */
    public function findByStoreAndType(ShopifyStore $store, string $syncType, int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.store = :store')
            ->andWhere('l.syncType = :type')
            ->setParameter('store', $store)
            ->setParameter('type', $syncType)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sync statistics for store
     */
    public function getSyncStatistics(ShopifyStore $store): array
    {
        $result = $this->createQueryBuilder('l')
            ->select('
                COUNT(l.id) as totalSyncs,
                SUM(l.recordsSynced) as totalRecords,
                SUM(l.recordsFailed) as totalFailed,
                AVG(TIMESTAMPDIFF(SECOND, l.startedAt, l.completedAt)) as avgDuration
            ')
            ->andWhere('l.store = :store')
            ->andWhere('l.status = :status')
            ->setParameter('store', $store)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleResult();

        return [
            'totalSyncs' => (int) $result['totalSyncs'],
            'totalRecords' => (int) $result['totalRecords'],
            'totalFailed' => (int) $result['totalFailed'],
            'avgDuration' => round((float) $result['avgDuration'], 2)
        ];
    }

    /**
     * Find failed syncs
     */
    public function findFailedSyncs(ShopifyStore $store, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.store = :store')
            ->andWhere('l.status = :status')
            ->setParameter('store', $store)
            ->setParameter('status', 'failed')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
