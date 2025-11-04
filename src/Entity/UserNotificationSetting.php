<?php

namespace App\Entity;

use App\Repository\UserNotificationSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Kullanıcıya özel bildirim servisi ayarları
 * SMS, Email, Push Notification vb. için
 */
#[ORM\Entity(repositoryClass: UserNotificationSettingRepository::class)]
#[ORM\Table(name: '`user_notification_settings`')]
#[ORM\HasLifecycleCallbacks]
class UserNotificationSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $channel = 'sms'; // sms, email, whatsapp, push

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $provider = 'netgsm'; // netgsm, iletimerkezi, twilio, etc.

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDefault = false;

    // SMS Provider Bilgileri
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $apiUsername = null; // Encrypted

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $apiPassword = null; // Encrypted

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $apiKey = null; // Encrypted

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $smsHeader = null; // SMS başlığı (örn: MYMARKA)

    // Email Provider Bilgileri
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $smtpHost = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $smtpPort = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $smtpUsername = null; // Encrypted

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $smtpPassword = null; // Encrypted

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $smtpEncryption = 'tls'; // tls, ssl, none

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fromEmail = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $fromName = null;

    // WhatsApp Business API
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $whatsappBusinessId = null; // Encrypted

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $whatsappAccessToken = null; // Encrypted

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $whatsappPhoneNumber = null;

    // Bildirim Tercihleri (hangi durumlarda gönderilsin)
    #[ORM\Column(type: Types::JSON)]
    private array $notificationPreferences = [
        'order_created' => true,
        'order_processing' => true,
        'shipment_created' => true,
        'shipment_picked_up' => true,
        'shipment_in_transit' => true,
        'shipment_out_for_delivery' => true,
        'shipment_delivered' => true,
        'shipment_failed' => true,
        'payment_received' => true,
        'low_credit' => true,
    ];

    // Müşteriye gönderim ayarları
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $sendToCustomer = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $sendToAdmin = false;

    // Şablon ayarları
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $messageTemplates = null; // Özel mesaj şablonları

    // Limit ve kota
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $monthlyQuota = null; // Aylık SMS/Email kotası

    #[ORM\Column(type: Types::INTEGER)]
    private int $monthlyUsage = 0; // Bu ay kullanılan miktar

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 4, nullable: true)]
    private ?string $costPerMessage = null; // Mesaj başı maliyet

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTestedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isTestSuccessful = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastTestError = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
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

    public function getApiUsername(): ?string
    {
        return $this->apiUsername;
    }

    public function setApiUsername(?string $apiUsername): static
    {
        $this->apiUsername = $apiUsername;
        return $this;
    }

    public function getApiPassword(): ?string
    {
        return $this->apiPassword;
    }

    public function setApiPassword(?string $apiPassword): static
    {
        $this->apiPassword = $apiPassword;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;
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

    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(?string $smtpHost): static
    {
        $this->smtpHost = $smtpHost;
        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): static
    {
        $this->smtpPort = $smtpPort;
        return $this;
    }

    public function getSmtpUsername(): ?string
    {
        return $this->smtpUsername;
    }

    public function setSmtpUsername(?string $smtpUsername): static
    {
        $this->smtpUsername = $smtpUsername;
        return $this;
    }

    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }

    public function setSmtpPassword(?string $smtpPassword): static
    {
        $this->smtpPassword = $smtpPassword;
        return $this;
    }

    public function getSmtpEncryption(): ?string
    {
        return $this->smtpEncryption;
    }

    public function setSmtpEncryption(?string $smtpEncryption): static
    {
        $this->smtpEncryption = $smtpEncryption;
        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(?string $fromEmail): static
    {
        $this->fromEmail = $fromEmail;
        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): static
    {
        $this->fromName = $fromName;
        return $this;
    }

    public function getWhatsappBusinessId(): ?string
    {
        return $this->whatsappBusinessId;
    }

    public function setWhatsappBusinessId(?string $whatsappBusinessId): static
    {
        $this->whatsappBusinessId = $whatsappBusinessId;
        return $this;
    }

    public function getWhatsappAccessToken(): ?string
    {
        return $this->whatsappAccessToken;
    }

    public function setWhatsappAccessToken(?string $whatsappAccessToken): static
    {
        $this->whatsappAccessToken = $whatsappAccessToken;
        return $this;
    }

    public function getWhatsappPhoneNumber(): ?string
    {
        return $this->whatsappPhoneNumber;
    }

    public function setWhatsappPhoneNumber(?string $whatsappPhoneNumber): static
    {
        $this->whatsappPhoneNumber = $whatsappPhoneNumber;
        return $this;
    }

    public function getNotificationPreferences(): array
    {
        return $this->notificationPreferences;
    }

    public function setNotificationPreferences(array $notificationPreferences): static
    {
        $this->notificationPreferences = $notificationPreferences;
        return $this;
    }

    public function isSendToCustomer(): bool
    {
        return $this->sendToCustomer;
    }

    public function setSendToCustomer(bool $sendToCustomer): static
    {
        $this->sendToCustomer = $sendToCustomer;
        return $this;
    }

    public function isSendToAdmin(): bool
    {
        return $this->sendToAdmin;
    }

    public function setSendToAdmin(bool $sendToAdmin): static
    {
        $this->sendToAdmin = $sendToAdmin;
        return $this;
    }

    public function getMessageTemplates(): ?array
    {
        return $this->messageTemplates;
    }

    public function setMessageTemplates(?array $messageTemplates): static
    {
        $this->messageTemplates = $messageTemplates;
        return $this;
    }

    public function getMonthlyQuota(): ?int
    {
        return $this->monthlyQuota;
    }

    public function setMonthlyQuota(?int $monthlyQuota): static
    {
        $this->monthlyQuota = $monthlyQuota;
        return $this;
    }

    public function getMonthlyUsage(): int
    {
        return $this->monthlyUsage;
    }

    public function setMonthlyUsage(int $monthlyUsage): static
    {
        $this->monthlyUsage = $monthlyUsage;
        return $this;
    }

    public function incrementMonthlyUsage(int $count = 1): static
    {
        $this->monthlyUsage += $count;
        return $this;
    }

    public function resetMonthlyUsage(): static
    {
        $this->monthlyUsage = 0;
        return $this;
    }

    public function getCostPerMessage(): ?string
    {
        return $this->costPerMessage;
    }

    public function setCostPerMessage(?string $costPerMessage): static
    {
        $this->costPerMessage = $costPerMessage;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getLastTestedAt(): ?\DateTimeInterface
    {
        return $this->lastTestedAt;
    }

    public function setLastTestedAt(?\DateTimeInterface $lastTestedAt): static
    {
        $this->lastTestedAt = $lastTestedAt;
        return $this;
    }

    public function isTestSuccessful(): bool
    {
        return $this->isTestSuccessful;
    }

    public function setIsTestSuccessful(bool $isTestSuccessful): static
    {
        $this->isTestSuccessful = $isTestSuccessful;
        return $this;
    }

    public function getLastTestError(): ?string
    {
        return $this->lastTestError;
    }

    public function setLastTestError(?string $lastTestError): static
    {
        $this->lastTestError = $lastTestError;
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

    public function hasQuotaRemaining(): bool
    {
        if (!$this->monthlyQuota) {
            return true; // No limit
        }
        return $this->monthlyUsage < $this->monthlyQuota;
    }

    public function getRemainingQuota(): ?int
    {
        if (!$this->monthlyQuota) {
            return null;
        }
        return max(0, $this->monthlyQuota - $this->monthlyUsage);
    }

    public function isNotificationEnabled(string $event): bool
    {
        return $this->notificationPreferences[$event] ?? false;
    }
}
