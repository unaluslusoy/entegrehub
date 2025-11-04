<?php

namespace App\Entity;

use App\Repository\UserNotificationTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNotificationTemplateRepository::class)]
#[ORM\Table(name: 'user_notification_templates')]
#[ORM\HasLifecycleCallbacks]
class UserNotificationTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notificationTemplates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $eventType = null; // order_created, shipment_delivered, etc.

    #[ORM\Column(length: 50)]
    private ?string $channel = null; // sms, whatsapp, email

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $subject = null; // For email

    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $availableVariables = null; // List of variables user can use

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDefault = false; // System default template

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        
        // Set default available variables based on event type
        if ($this->availableVariables === null) {
            $this->availableVariables = $this->getDefaultVariablesForEventType($this->eventType);
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function getDefaultVariablesForEventType(?string $eventType): array
    {
        $common = [
            'user_name' => 'Kullanıcı adı',
            'user_email' => 'Kullanıcı e-posta',
            'company_name' => 'Firma adı',
        ];

        return match ($eventType) {
            'order_created' => array_merge($common, [
                'order_number' => 'Sipariş numarası',
                'order_total' => 'Sipariş tutarı',
                'order_items_count' => 'Ürün sayısı',
                'customer_name' => 'Müşteri adı',
                'customer_phone' => 'Müşteri telefonu',
            ]),
            'order_cancelled' => array_merge($common, [
                'order_number' => 'Sipariş numarası',
                'cancellation_reason' => 'İptal nedeni',
            ]),
            'shipment_created' => array_merge($common, [
                'tracking_number' => 'Takip numarası',
                'cargo_company' => 'Kargo firması',
                'customer_name' => 'Müşteri adı',
                'customer_phone' => 'Müşteri telefonu',
                'customer_address' => 'Teslimat adresi',
            ]),
            'shipment_picked_up' => array_merge($common, [
                'tracking_number' => 'Takip numarası',
                'cargo_company' => 'Kargo firması',
                'pickup_date' => 'Teslim alınma tarihi',
            ]),
            'shipment_in_transit' => array_merge($common, [
                'tracking_number' => 'Takip numarası',
                'cargo_company' => 'Kargo firması',
                'current_location' => 'Mevcut konum',
            ]),
            'shipment_delivered' => array_merge($common, [
                'tracking_number' => 'Takip numarası',
                'cargo_company' => 'Kargo firması',
                'delivered_date' => 'Teslim tarihi',
                'receiver_name' => 'Teslim alan',
            ]),
            'payment_received' => array_merge($common, [
                'payment_amount' => 'Ödeme tutarı',
                'payment_method' => 'Ödeme yöntemi',
                'transaction_id' => 'İşlem numarası',
            ]),
            default => $common,
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getAvailableVariables(): ?array
    {
        return $this->availableVariables;
    }

    public function setAvailableVariables(?array $availableVariables): static
    {
        $this->availableVariables = $availableVariables;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function renderTemplate(array $data): string
    {
        $body = $this->body;
        
        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }
        
        return $body;
    }
}
