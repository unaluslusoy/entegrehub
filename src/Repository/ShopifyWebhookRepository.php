<?php

namespace App\Repository;

use App\Entity\ShopifyWebhook;
use App\Entity\ShopifyStore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopifyWebhook>
 */
class ShopifyWebhookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopifyWebhook::class);
    }

    public function save(ShopifyWebhook $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShopifyWebhook $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find active webhooks by store
     */
    public function findActiveByStore(ShopifyStore $store): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.store = :store')
            ->andWhere('w.isActive = :active')
            ->setParameter('store', $store)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find webhook by store and topic
     */
    public function findByStoreAndTopic(ShopifyStore $store, string $topic): ?ShopifyWebhook
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.store = :store')
            ->andWhere('w.topic = :topic')
            ->setParameter('store', $store)
            ->setParameter('topic', $topic)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
