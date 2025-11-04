<?php

namespace App\Entity;

use App\Repository\UserNotificationConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserNotificationConfigRepository::class)]
#[ORM\Table(name: 'user_notification_configs')]
#[ORM\HasLifecycleCallbacks]
class UserNotificationConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'notificationConfig')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // SMS Configuration
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $smsProvider = null; // netgsm, iletimerkezi, twilio

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $smsCredentials = null; // username, password, api_key, etc.

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $smsHeader = null; // Corporate SMS header/sender name

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $smsEnabled = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $smsTestMode = false;

    // WhatsApp Configuration
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $whatsappProvider = null; // whatsapp_business_api, twilio, custom

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $whatsappCredentials = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $whatsappNumber = null; // Business phone number

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $whatsappEnabled = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $whatsappTestMode = false;

    // Email Configuration (optional custom SMTP)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $emailCredentials = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $useCustomEmail = false;

    // Notification Preferences
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $notificationSettings = null; // Which events trigger notifications

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTestAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        
        // Default notification settings
        if ($this->notificationSettings === null) {
            $this->notificationSettings = [
                'order_created' => ['sms' => true, 'whatsapp' => true, 'email' => true],
                'order_cancelled' => ['sms' => true, 'whatsapp' => true, 'email' => true],
                'shipment_created' => ['sms' => true, 'whatsapp' => true, 'email' => true],
                'shipment_picked_up' => ['sms' => true, 'whatsapp' => false, 'email' => false],
                'shipment_in_transit' => ['sms' => false, 'whatsapp' => false, 'email' => false],
                'shipment_delivered' => ['sms' => true, 'whatsapp' => true, 'email' => true],
                'payment_received' => ['sms' => false, 'whatsapp' => false, 'email' => true],
            ];
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSmsProvider(): ?string
    {
        return $this->smsProvider;
    }

    public function setSmsProvider(?string $smsProvider): static
    {
        $this->smsProvider = $smsProvider;
        return $this;
    }

    public function getSmsCredentials(): ?array
    {
        return $this->smsCredentials;
    }

    public function setSmsCredentials(?array $smsCredentials): static
    {
        $this->smsCredentials = $smsCredentials;
        return $this;
    }

    public function getSmsHeader(): ?string
    {
        return $this->smsHeader;
    }

    public function setSmsHeader(?string $smsHeader): static
    {
        $this->smsHeader = $smsHeader;
        return $this;
    }

    public function isSmsEnabled(): bool
    {
        return $this->smsEnabled;
    }

    public function setSmsEnabled(bool $smsEnabled): static
    {
        $this->smsEnabled = $smsEnabled;
        return $this;
    }

    public function isSmsTestMode(): bool
    {
        return $this->smsTestMode;
    }

    public function setSmsTestMode(bool $smsTestMode): static
    {
        $this->smsTestMode = $smsTestMode;
        return $this;
    }

    public function getWhatsappProvider(): ?string
    {
        return $this->whatsappProvider;
    }

    public function setWhatsappProvider(?string $whatsappProvider): static
    {
        $this->whatsappProvider = $whatsappProvider;
        return $this;
    }

    public function getWhatsappCredentials(): ?array
    {
        return $this->whatsappCredentials;
    }

    public function setWhatsappCredentials(?array $whatsappCredentials): static
    {
        $this->whatsappCredentials = $whatsappCredentials;
        return $this;
    }

    public function getWhatsappNumber(): ?string
    {
        return $this->whatsappNumber;
    }

    public function setWhatsappNumber(?string $whatsappNumber): static
    {
        $this->whatsappNumber = $whatsappNumber;
        return $this;
    }

    public function isWhatsappEnabled(): bool
    {
        return $this->whatsappEnabled;
    }

    public function setWhatsappEnabled(bool $whatsappEnabled): static
    {
        $this->whatsappEnabled = $whatsappEnabled;
        return $this;
    }

    public function isWhatsappTestMode(): bool
    {
        return $this->whatsappTestMode;
    }

    public function setWhatsappTestMode(bool $whatsappTestMode): static
    {
        $this->whatsappTestMode = $whatsappTestMode;
        return $this;
    }

    public function getEmailCredentials(): ?array
    {
        return $this->emailCredentials;
    }

    public function setEmailCredentials(?array $emailCredentials): static
    {
        $this->emailCredentials = $emailCredentials;
        return $this;
    }

    public function isUseCustomEmail(): bool
    {
        return $this->useCustomEmail;
    }

    public function setUseCustomEmail(bool $useCustomEmail): static
    {
        $this->useCustomEmail = $useCustomEmail;
        return $this;
    }

    public function getNotificationSettings(): ?array
    {
        return $this->notificationSettings;
    }

    public function setNotificationSettings(?array $notificationSettings): static
    {
        $this->notificationSettings = $notificationSettings;
        return $this;
    }

    public function getLastTestAt(): ?\DateTimeInterface
    {
        return $this->lastTestAt;
    }

    public function setLastTestAt(?\DateTimeInterface $lastTestAt): static
    {
        $this->lastTestAt = $lastTestAt;
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
}
