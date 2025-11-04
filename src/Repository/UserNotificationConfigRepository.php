<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotificationConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotificationConfig>
 */
class UserNotificationConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotificationConfig::class);
    }

    public function findByUser(User $user): ?UserNotificationConfig
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findOrCreateForUser(User $user): UserNotificationConfig
    {
        $config = $this->findByUser($user);
        
        if ($config === null) {
            $config = new UserNotificationConfig();
            $config->setUser($user);
            $this->getEntityManager()->persist($config);
            $this->getEntityManager()->flush();
        }
        
        return $config;
    }

    public function findAllWithSmsEnabled(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.smsEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithWhatsappEnabled(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.whatsappEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult();
    }

    public function countConfiguredUsers(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.smsEnabled = :enabled OR c.whatsappEnabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
