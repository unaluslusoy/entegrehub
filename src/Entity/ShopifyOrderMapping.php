<?php

namespace App\Entity;

use App\Repository\ShopifyOrderMappingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopifyOrderMappingRepository::class)]
#[ORM\Table(name: 'shopify_order_mappings')]
#[ORM\Index(name: 'idx_store_id', columns: ['store_id'])]
#[ORM\Index(name: 'idx_shopify_order_id', columns: ['shopify_order_id'])]
#[ORM\Index(name: 'idx_internal_order_id', columns: ['internal_order_id'])]
#[ORM\Index(name: 'idx_sync_status', columns: ['sync_status'])]
#[ORM\UniqueConstraint(name: 'unique_shopify_order', columns: ['store_id', 'shopify_order_id'])]
class ShopifyOrderMapping
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopifyStore::class, inversedBy: 'orderMappings')]
    #[ORM\JoinColumn(name: 'store_id', nullable: false, onDelete: 'CASCADE')]
    private ?ShopifyStore $store = null;

    #[ORM\Column(length: 100)]
    private ?string $shopifyOrderId = null;

    #[ORM\Column(length: 100)]
    private ?string $shopifyOrderNumber = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $internalOrderId = null;

    #[ORM\Column(length: 50, options: ['default' => 'pending'])]
    private string $syncStatus = 'pending';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastSyncAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $shopifyData = null;

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

    public function getStore(): ?ShopifyStore
    {
        return $this->store;
    }

    public function setStore(?ShopifyStore $store): static
    {
        $this->store = $store;
        return $this;
    }

    public function getShopifyOrderId(): ?string
    {
        return $this->shopifyOrderId;
    }

    public function setShopifyOrderId(string $shopifyOrderId): static
    {
        $this->shopifyOrderId = $shopifyOrderId;
        return $this;
    }

    public function getShopifyOrderNumber(): ?string
    {
        return $this->shopifyOrderNumber;
    }

    public function setShopifyOrderNumber(string $shopifyOrderNumber): static
    {
        $this->shopifyOrderNumber = $shopifyOrderNumber;
        return $this;
    }

    public function getInternalOrderId(): ?int
    {
        return $this->internalOrderId;
    }

    public function setInternalOrderId(?int $internalOrderId): static
    {
        $this->internalOrderId = $internalOrderId;
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

    public function getLastSyncAt(): ?\DateTimeInterface
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeInterface $lastSyncAt): static
    {
        $this->lastSyncAt = $lastSyncAt;
        return $this;
    }

    public function getShopifyData(): ?array
    {
        return $this->shopifyData;
    }

    public function setShopifyData(?array $shopifyData): static
    {
        $this->shopifyData = $shopifyData;
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
}
