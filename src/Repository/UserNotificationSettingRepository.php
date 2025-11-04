<?php

namespace App\Repository;

use App\Entity\UserNotificationSetting;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserNotificationSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotificationSetting::class);
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('uns')
            ->where('uns.user = :user')
            ->andWhere('uns.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('uns.channel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndChannel(User $user, string $channel): array
    {
        return $this->createQueryBuilder('uns')
            ->where('uns.user = :user')
            ->andWhere('uns.channel = :channel')
            ->andWhere('uns.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('channel', $channel)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findDefaultByUserAndChannel(User $user, string $channel): ?UserNotificationSetting
    {
        return $this->createQueryBuilder('uns')
            ->where('uns.user = :user')
            ->andWhere('uns.channel = :channel')
            ->andWhere('uns.isActive = :active')
            ->andWhere('uns.isDefault = :default')
            ->setParameter('user', $user)
            ->setParameter('channel', $channel)
            ->setParameter('active', true)
            ->setParameter('default', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
