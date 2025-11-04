<?php

namespace App\Entity;

use App\Repository\UserCargoCompanyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Kullanıcıya özel kargo firması ayarları
 * Her kullanıcı kendi API bilgilerini buraya girecek
 */
#[ORM\Entity(repositoryClass: UserCargoCompanyRepository::class)]
#[ORM\Table(name: '`user_cargo_companies`')]
#[ORM\HasLifecycleCallbacks]
class UserCargoCompany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?CargoCompany $cargoCompany = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDefault = false;

    #[ORM\Column(type: Types::INTEGER)]
    private int $priority = 0; // Otomatik seçim için öncelik

    #[ORM\Column(type: Types::TEXT)]
    private ?string $apiUsername = null; // Encrypted

    #[ORM\Column(type: Types::TEXT)]
    private ?string $apiPassword = null; // Encrypted

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerId = null; // Encrypted (Müşteri No, İşlem No vs.)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $additionalCredentials = null; // Şifrelenmiş ekstra bilgiler

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $serviceSettings = null; // Servis ayarları (standart/express vs.)

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $negotiatedBaseCost = null; // Anlaşmalı fiyat

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $negotiatedCostPerKg = null; // Anlaşmalı kg başı fiyat

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contractNumber = null; // Sözleşme numarası

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $contractStartDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $contractEndDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTestedAt = null; // Son API test tarihi

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isTestSuccessful = false; // Son test başarılı mı?

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

    public function getCargoCompany(): ?CargoCompany
    {
        return $this->cargoCompany;
    }

    public function setCargoCompany(?CargoCompany $cargoCompany): static
    {
        $this->cargoCompany = $cargoCompany;
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

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getApiUsername(): ?string
    {
        return $this->apiUsername;
    }

    public function setApiUsername(string $apiUsername): static
    {
        $this->apiUsername = $apiUsername;
        return $this;
    }

    public function getApiPassword(): ?string
    {
        return $this->apiPassword;
    }

    public function setApiPassword(string $apiPassword): static
    {
        $this->apiPassword = $apiPassword;
        return $this;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): static
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getAdditionalCredentials(): ?array
    {
        return $this->additionalCredentials;
    }

    public function setAdditionalCredentials(?array $additionalCredentials): static
    {
        $this->additionalCredentials = $additionalCredentials;
        return $this;
    }

    public function getServiceSettings(): ?array
    {
        return $this->serviceSettings;
    }

    public function setServiceSettings(?array $serviceSettings): static
    {
        $this->serviceSettings = $serviceSettings;
        return $this;
    }

    public function getNegotiatedBaseCost(): ?string
    {
        return $this->negotiatedBaseCost;
    }

    public function setNegotiatedBaseCost(?string $negotiatedBaseCost): static
    {
        $this->negotiatedBaseCost = $negotiatedBaseCost;
        return $this;
    }

    public function getNegotiatedCostPerKg(): ?string
    {
        return $this->negotiatedCostPerKg;
    }

    public function setNegotiatedCostPerKg(?string $negotiatedCostPerKg): static
    {
        $this->negotiatedCostPerKg = $negotiatedCostPerKg;
        return $this;
    }

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(?string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;
        return $this;
    }

    public function getContractStartDate(): ?\DateTimeInterface
    {
        return $this->contractStartDate;
    }

    public function setContractStartDate(?\DateTimeInterface $contractStartDate): static
    {
        $this->contractStartDate = $contractStartDate;
        return $this;
    }

    public function getContractEndDate(): ?\DateTimeInterface
    {
        return $this->contractEndDate;
    }

    public function setContractEndDate(?\DateTimeInterface $contractEndDate): static
    {
        $this->contractEndDate = $contractEndDate;
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

    public function isContractValid(): bool
    {
        if (!$this->contractEndDate) {
            return true;
        }
        return $this->contractEndDate >= new \DateTime();
    }
}
