<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserNotificationTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotificationTemplate>
 */
class UserNotificationTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotificationTemplate::class);
    }

    public function findByUser(User $user, bool $activeOnly = false): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.eventType', 'ASC')
            ->addOrderBy('t.channel', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('t.isActive = :active')
                ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByUserAndEvent(User $user, string $eventType, string $channel): ?UserNotificationTemplate
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.eventType = :eventType')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('eventType', $eventType)
            ->setParameter('channel', $channel)
            ->setParameter('active', true)
            ->orderBy('t.isDefault', 'DESC') // User templates override defaults
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDefaultTemplates(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isDefault = :default')
            ->andWhere('t.isActive = :active')
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->orderBy('t.eventType', 'ASC')
            ->addOrderBy('t.channel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAvailableEventTypes(): array
    {
        return [
            'order_created' => 'Sipariş Oluşturuldu',
            'order_cancelled' => 'Sipariş İptal Edildi',
            'shipment_created' => 'Kargo Oluşturuldu',
            'shipment_picked_up' => 'Kargo Teslim Alındı',
            'shipment_in_transit' => 'Kargo Yolda',
            'shipment_delivered' => 'Kargo Teslim Edildi',
            'payment_received' => 'Ödeme Alındı',
        ];
    }

    public function findAvailableChannels(): array
    {
        return [
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'email' => 'E-posta',
        ];
    }
}
