<?php

namespace App\Repository;

use App\Entity\UserLabelTemplate;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLabelTemplate>
 */
class UserLabelTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLabelTemplate::class);
    }

    /**
     * Find all active templates for a user
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('t.isDefault', 'DESC')
            ->addOrderBy('t.usageCount', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default template for a user
     */
    public function findDefaultByUser(User $user): ?UserLabelTemplate
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.isDefault = :default')
            ->andWhere('t.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find templates by category for a user
     */
    public function findByUserAndCategory(User $user, string $category): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.category = :category')
            ->andWhere('t.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most used templates for a user
     */
    public function findMostUsedByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Set a template as default (and unset others)
     */
    public function setAsDefault(UserLabelTemplate $template): void
    {
        // First, unset all default templates for this user
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.isDefault', ':false')
            ->where('t.user = :user')
            ->setParameter('false', false)
            ->setParameter('user', $template->getUser())
            ->getQuery()
            ->execute();

        // Then set this template as default
        $template->setIsDefault(true);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete user templates by IDs (with ownership check)
     */
    public function deleteByUserAndIds(User $user, array $ids): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->andWhere('t.id IN (:ids)')
            ->setParameter('user', $user)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }

    /**
     * Count templates by user
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find recently created templates
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
