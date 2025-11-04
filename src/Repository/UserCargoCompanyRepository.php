<?php

namespace App\Repository;

use App\Entity\UserCargoCompany;
use App\Entity\User;
use App\Entity\CargoCompany;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserCargoCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCargoCompany::class);
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('ucc')
            ->join('ucc.cargoCompany', 'cc')
            ->where('ucc.user = :user')
            ->andWhere('ucc.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('ucc.priority', 'DESC')
            ->addOrderBy('cc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndCompany(User $user, CargoCompany $cargoCompany): ?UserCargoCompany
    {
        return $this->createQueryBuilder('ucc')
            ->where('ucc.user = :user')
            ->andWhere('ucc.cargoCompany = :cargoCompany')
            ->setParameter('user', $user)
            ->setParameter('cargoCompany', $cargoCompany)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultByUser(User $user): ?UserCargoCompany
    {
        return $this->createQueryBuilder('ucc')
            ->where('ucc.user = :user')
            ->andWhere('ucc.isActive = :active')
            ->andWhere('ucc.isDefault = :default')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('default', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('ucc')
            ->select('COUNT(ucc.id)')
            ->where('ucc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
