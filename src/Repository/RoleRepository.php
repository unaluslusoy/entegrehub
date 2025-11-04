<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function save(Role $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Role $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all roles ordered by level DESC
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.level', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find non-system roles that can be deleted
     */
    public function findNonSystem(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isSystem = :isSystem')
            ->setParameter('isSystem', false)
            ->orderBy('r.level', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find role by slug
     */
    public function findBySlug(string $slug): ?Role
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Count roles with user count
     */
    public function getRoleStats(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.id', 'r.name', 'r.slug', 'r.level', 'r.isSystem', 'COUNT(u.id) as user_count')
            ->leftJoin('r.users', 'u')
            ->groupBy('r.id', 'r.name', 'r.slug', 'r.level', 'r.isSystem')
            ->orderBy('r.level', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find roles with level less than or equal to maxLevel
     * Used for team member role assignment (excluding super admin)
     */
    public function findByMaxLevel(int $maxLevel): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.level <= :maxLevel')
            ->setParameter('maxLevel', $maxLevel)
            ->orderBy('r.level', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
