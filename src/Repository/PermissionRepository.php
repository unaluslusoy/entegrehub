<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    public function save(Permission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Permission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all permissions grouped by module
     */
    public function findAllGroupedByModule(): array
    {
        $permissions = $this->createQueryBuilder('p')
            ->orderBy('p.module', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission->getModule();
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Find permission by slug
     */
    public function findBySlug(string $slug): ?Permission
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * Find all permissions by module
     */
    public function findByModule(string $module): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.module = :module')
            ->setParameter('module', $module)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
