<?php

namespace App\Entity;

use App\Repository\UserCargoProviderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserCargoProviderRepository::class)]
#[ORM\Table(name: 'user_cargo_providers')]
#[ORM\HasLifecycleCallbacks]
class UserCargoProvider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customCargoProviders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiEndpoint = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $credentials = null; // API key, secret, customer code, etc.

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $configFields = null; // Field definitions for dynamic form

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $webhookUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $documentationUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $supportEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $supportPhone = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isTestMode = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTestAt = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $lastTestStatus = null; // success, failed

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastTestMessage = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;
        return $this;
    }

    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    public function setApiEndpoint(?string $apiEndpoint): static
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    public function getCredentials(): ?array
    {
        return $this->credentials;
    }

    public function setCredentials(?array $credentials): static
    {
        $this->credentials = $credentials;
        return $this;
    }

    public function getConfigFields(): ?array
    {
        return $this->configFields;
    }

    public function setConfigFields(?array $configFields): static
    {
        $this->configFields = $configFields;
        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): static
    {
        $this->webhookUrl = $webhookUrl;
        return $this;
    }

    public function getDocumentationUrl(): ?string
    {
        return $this->documentationUrl;
    }

    public function setDocumentationUrl(?string $documentationUrl): static
    {
        $this->documentationUrl = $documentationUrl;
        return $this;
    }

    public function getSupportEmail(): ?string
    {
        return $this->supportEmail;
    }

    public function setSupportEmail(?string $supportEmail): static
    {
        $this->supportEmail = $supportEmail;
        return $this;
    }

    public function getSupportPhone(): ?string
    {
        return $this->supportPhone;
    }

    public function setSupportPhone(?string $supportPhone): static
    {
        $this->supportPhone = $supportPhone;
        return $this;
    }

    public function isIsTestMode(): bool
    {
        return $this->isTestMode;
    }

    public function setIsTestMode(bool $isTestMode): static
    {
        $this->isTestMode = $isTestMode;
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function getLastTestStatus(): ?string
    {
        return $this->lastTestStatus;
    }

    public function setLastTestStatus(?string $lastTestStatus): static
    {
        $this->lastTestStatus = $lastTestStatus;
        return $this;
    }

    public function getLastTestMessage(): ?string
    {
        return $this->lastTestMessage;
    }

    public function setLastTestMessage(?string $lastTestMessage): static
    {
        $this->lastTestMessage = $lastTestMessage;
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
