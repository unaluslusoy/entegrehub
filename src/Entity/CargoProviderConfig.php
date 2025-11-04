<?php

namespace App\Entity;

use App\Repository\CargoProviderConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CargoProviderConfigRepository::class)]
#[ORM\Table(name: 'cargo_provider_configs')]
#[ORM\UniqueConstraint(name: 'unique_user_provider', columns: ['user_id', 'provider_id'])]
class CargoProviderConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: CargoProvider::class, inversedBy: 'userConfigs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CargoProvider $provider = null;

    #[ORM\Column(type: 'json')]
    private array $credentials = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webhookSecret = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isTestMode = true;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $testConnectionStatus = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $testConnectionMessage = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastTestAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getProvider(): ?CargoProvider
    {
        return $this->provider;
    }

    public function setProvider(?CargoProvider $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function setCredentials(array $credentials): static
    {
        $this->credentials = $credentials;
        return $this;
    }

    public function getWebhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    public function setWebhookSecret(?string $webhookSecret): static
    {
        $this->webhookSecret = $webhookSecret;
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

    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    public function setIsTestMode(bool $isTestMode): static
    {
        $this->isTestMode = $isTestMode;
        return $this;
    }

    public function getTestConnectionStatus(): ?string
    {
        return $this->testConnectionStatus;
    }

    public function setTestConnectionStatus(?string $testConnectionStatus): static
    {
        $this->testConnectionStatus = $testConnectionStatus;
        return $this;
    }

    public function getTestConnectionMessage(): ?string
    {
        return $this->testConnectionMessage;
    }

    public function setTestConnectionMessage(?string $testConnectionMessage): static
    {
        $this->testConnectionMessage = $testConnectionMessage;
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

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
