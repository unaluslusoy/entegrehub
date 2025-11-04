<?php

namespace App\Entity;

use App\Repository\ShopifyStoreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopifyStoreRepository::class)]
#[ORM\Table(name: 'shopify_stores')]
#[ORM\Index(name: 'idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_shop_domain', columns: ['shop_domain'])]
#[ORM\Index(name: 'idx_is_active', columns: ['is_active'])]
#[ORM\Index(name: 'idx_sync_status', columns: ['sync_status'])]
#[ORM\UniqueConstraint(name: 'unique_user_shop', columns: ['user_id', 'shop_domain'])]
class ShopifyStore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $shopDomain = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shopName = null;

    #[ORM\Column(type: 'text')]
    private ?string $accessToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiSecretKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webhookAddress = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(length: 50, options: ['default' => 'pending'])]
    private string $syncStatus = 'pending';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $syncProgress = 0;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastSyncAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastOrderSyncAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalOrdersSynced = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shopEmail = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $shopCurrency = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shopTimezone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shopPlan = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $scopes = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: ShopifyWebhook::class, cascade: ['remove'])]
    private Collection $webhooks;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: ShopifySyncLog::class, cascade: ['remove'])]
    private Collection $syncLogs;

    #[ORM\OneToMany(mappedBy: 'store', targetEntity: ShopifyOrderMapping::class, cascade: ['remove'])]
    private Collection $orderMappings;

    public function __construct()
    {
        $this->webhooks = new ArrayCollection();
        $this->syncLogs = new ArrayCollection();
        $this->orderMappings = new ArrayCollection();
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

    public function getShopDomain(): ?string
    {
        return $this->shopDomain;
    }

    public function setShopDomain(string $shopDomain): static
    {
        $this->shopDomain = $shopDomain;
        return $this;
    }

    public function getShopName(): ?string
    {
        return $this->shopName;
    }

    public function setShopName(?string $shopName): static
    {
        $this->shopName = $shopName;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;
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

    public function getApiSecretKey(): ?string
    {
        return $this->apiSecretKey;
    }

    public function setApiSecretKey(?string $apiSecretKey): static
    {
        $this->apiSecretKey = $apiSecretKey;
        return $this;
    }

    public function getWebhookAddress(): ?string
    {
        return $this->webhookAddress;
    }

    public function setWebhookAddress(?string $webhookAddress): static
    {
        $this->webhookAddress = $webhookAddress;
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

    public function getSyncStatus(): string
    {
        return $this->syncStatus;
    }

    public function setSyncStatus(string $syncStatus): static
    {
        $this->syncStatus = $syncStatus;
        return $this;
    }

    public function getSyncProgress(): int
    {
        return $this->syncProgress;
    }

    public function setSyncProgress(int $syncProgress): static
    {
        $this->syncProgress = $syncProgress;
        return $this;
    }

    public function getLastSyncAt(): ?\DateTimeInterface
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeInterface $lastSyncAt): static
    {
        $this->lastSyncAt = $lastSyncAt;
        return $this;
    }

    public function getLastOrderSyncAt(): ?\DateTimeInterface
    {
        return $this->lastOrderSyncAt;
    }

    public function setLastOrderSyncAt(?\DateTimeInterface $lastOrderSyncAt): static
    {
        $this->lastOrderSyncAt = $lastOrderSyncAt;
        return $this;
    }

    public function getTotalOrdersSynced(): int
    {
        return $this->totalOrdersSynced;
    }

    public function setTotalOrdersSynced(int $totalOrdersSynced): static
    {
        $this->totalOrdersSynced = $totalOrdersSynced;
        return $this;
    }

    public function getShopEmail(): ?string
    {
        return $this->shopEmail;
    }

    public function setShopEmail(?string $shopEmail): static
    {
        $this->shopEmail = $shopEmail;
        return $this;
    }

    public function getShopCurrency(): ?string
    {
        return $this->shopCurrency;
    }

    public function setShopCurrency(?string $shopCurrency): static
    {
        $this->shopCurrency = $shopCurrency;
        return $this;
    }

    public function getShopTimezone(): ?string
    {
        return $this->shopTimezone;
    }

    public function setShopTimezone(?string $shopTimezone): static
    {
        $this->shopTimezone = $shopTimezone;
        return $this;
    }

    public function getShopPlan(): ?string
    {
        return $this->shopPlan;
    }

    public function setShopPlan(?string $shopPlan): static
    {
        $this->shopPlan = $shopPlan;
        return $this;
    }

    public function getScopes(): ?string
    {
        return $this->scopes;
    }

    public function setScopes(?string $scopes): static
    {
        $this->scopes = $scopes;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
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

    /**
     * @return Collection<int, ShopifyWebhook>
     */
    public function getWebhooks(): Collection
    {
        return $this->webhooks;
    }

    public function addWebhook(ShopifyWebhook $webhook): static
    {
        if (!$this->webhooks->contains($webhook)) {
            $this->webhooks->add($webhook);
            $webhook->setStore($this);
        }
        return $this;
    }

    public function removeWebhook(ShopifyWebhook $webhook): static
    {
        if ($this->webhooks->removeElement($webhook)) {
            if ($webhook->getStore() === $this) {
                $webhook->setStore(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ShopifySyncLog>
     */
    public function getSyncLogs(): Collection
    {
        return $this->syncLogs;
    }

    /**
     * @return Collection<int, ShopifyOrderMapping>
     */
    public function getOrderMappings(): Collection
    {
        return $this->orderMappings;
    }

    // Statistics helpers
    public function getTotalOrdersCount(): int
    {
        return $this->totalOrdersSynced;
    }

    public function setTotalOrdersCount(int $count): static
    {
        $this->totalOrdersSynced = $count;
        return $this;
    }

    public function getSyncedOrdersCount(): int
    {
        return $this->totalOrdersSynced;
    }

    public function setSyncedOrdersCount(int $count): static
    {
        $this->totalOrdersSynced = $count;
        return $this;
    }
}
