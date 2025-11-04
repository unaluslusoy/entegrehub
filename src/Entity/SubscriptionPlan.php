<?php

namespace App\Entity;

use App\Repository\SubscriptionPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Sistem abonelik paketleri (Free, Starter, Growth, Business, Enterprise)
 */
#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: '`subscription_plans`')]
#[ORM\HasLifecycleCallbacks]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null; // free, starter, growth, business, enterprise

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $monthlyPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $yearlyPrice = '0.00';

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxOrders = 50; // Aylık sipariş limiti

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxShops = 1; // Mağaza sayısı limiti

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxUsers = 1; // Alt kullanıcı sayısı limiti

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxSmsPerMonth = null; // Aylık SMS limiti (null = sınırsız)

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxEmailPerMonth = null; // Aylık Email limiti

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasApiAccess = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasAdvancedReports = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasBarcodeScanner = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasAiFeatures = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasWhiteLabel = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasPrioritySupport = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasCustomDomain = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $features = null; // Ekstra özellikler listesi

    #[ORM\Column(type: Types::INTEGER)]
    private int $priority = 0; // Gösterim sırası

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isPopular = false; // "Most Popular" rozeti

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: UserSubscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getMonthlyPrice(): ?string
    {
        return $this->monthlyPrice;
    }

    public function setMonthlyPrice(string $monthlyPrice): static
    {
        $this->monthlyPrice = $monthlyPrice;
        return $this;
    }

    public function getYearlyPrice(): ?string
    {
        return $this->yearlyPrice;
    }

    public function setYearlyPrice(string $yearlyPrice): static
    {
        $this->yearlyPrice = $yearlyPrice;
        return $this;
    }

    public function getMaxOrders(): int
    {
        return $this->maxOrders;
    }

    public function setMaxOrders(int $maxOrders): static
    {
        $this->maxOrders = $maxOrders;
        return $this;
    }

    public function getMaxShops(): int
    {
        return $this->maxShops;
    }

    public function setMaxShops(int $maxShops): static
    {
        $this->maxShops = $maxShops;
        return $this;
    }

    public function getMaxUsers(): int
    {
        return $this->maxUsers;
    }

    public function setMaxUsers(int $maxUsers): static
    {
        $this->maxUsers = $maxUsers;
        return $this;
    }

    public function getMaxSmsPerMonth(): ?int
    {
        return $this->maxSmsPerMonth;
    }

    public function setMaxSmsPerMonth(?int $maxSmsPerMonth): static
    {
        $this->maxSmsPerMonth = $maxSmsPerMonth;
        return $this;
    }

    public function getMaxEmailPerMonth(): ?int
    {
        return $this->maxEmailPerMonth;
    }

    public function setMaxEmailPerMonth(?int $maxEmailPerMonth): static
    {
        $this->maxEmailPerMonth = $maxEmailPerMonth;
        return $this;
    }

    public function hasApiAccess(): bool
    {
        return $this->hasApiAccess;
    }

    public function setHasApiAccess(bool $hasApiAccess): static
    {
        $this->hasApiAccess = $hasApiAccess;
        return $this;
    }

    public function hasAdvancedReports(): bool
    {
        return $this->hasAdvancedReports;
    }

    public function setHasAdvancedReports(bool $hasAdvancedReports): static
    {
        $this->hasAdvancedReports = $hasAdvancedReports;
        return $this;
    }

    public function hasBarcodeScanner(): bool
    {
        return $this->hasBarcodeScanner;
    }

    public function setHasBarcodeScanner(bool $hasBarcodeScanner): static
    {
        $this->hasBarcodeScanner = $hasBarcodeScanner;
        return $this;
    }

    public function hasAiFeatures(): bool
    {
        return $this->hasAiFeatures;
    }

    public function setHasAiFeatures(bool $hasAiFeatures): static
    {
        $this->hasAiFeatures = $hasAiFeatures;
        return $this;
    }

    public function hasWhiteLabel(): bool
    {
        return $this->hasWhiteLabel;
    }

    public function setHasWhiteLabel(bool $hasWhiteLabel): static
    {
        $this->hasWhiteLabel = $hasWhiteLabel;
        return $this;
    }

    public function hasPrioritySupport(): bool
    {
        return $this->hasPrioritySupport;
    }

    public function setHasPrioritySupport(bool $hasPrioritySupport): static
    {
        $this->hasPrioritySupport = $hasPrioritySupport;
        return $this;
    }

    public function hasCustomDomain(): bool
    {
        return $this->hasCustomDomain;
    }

    public function setHasCustomDomain(bool $hasCustomDomain): static
    {
        $this->hasCustomDomain = $hasCustomDomain;
        return $this;
    }

    public function getFeatures(): ?array
    {
        return $this->features;
    }

    public function setFeatures(?array $features): static
    {
        $this->features = $features;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isPopular(): bool
    {
        return $this->isPopular;
    }

    public function setIsPopular(bool $isPopular): static
    {
        $this->isPopular = $isPopular;
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

    /**
     * @return Collection<int, UserSubscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function isFree(): bool
    {
        return $this->code === 'free';
    }
}
