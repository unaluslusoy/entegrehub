<?php

namespace App\Entity;

use App\Repository\ShopifyWebhookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopifyWebhookRepository::class)]
#[ORM\Table(name: 'shopify_webhooks')]
#[ORM\Index(name: 'idx_store_id', columns: ['store_id'])]
#[ORM\Index(name: 'idx_topic', columns: ['topic'])]
#[ORM\Index(name: 'idx_is_active', columns: ['is_active'])]
#[ORM\UniqueConstraint(name: 'unique_store_topic', columns: ['store_id', 'topic'])]
class ShopifyWebhook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopifyStore::class, inversedBy: 'webhooks')]
    #[ORM\JoinColumn(name: 'store_id', nullable: false, onDelete: 'CASCADE')]
    private ?ShopifyStore $store = null;

    #[ORM\Column(length: 100)]
    private ?string $topic = null;

    #[ORM\Column(length: 100)]
    private ?string $webhookId = null;

    #[ORM\Column(length: 500)]
    private ?string $address = null;

    #[ORM\Column(length: 20, options: ['default' => 'json'])]
    private string $format = 'json';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

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

    public function getTopic(): ?string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): static
    {
        $this->topic = $topic;
        return $this;
    }

    public function getWebhookId(): ?string
    {
        return $this->webhookId;
    }

    public function setWebhookId(string $webhookId): static
    {
        $this->webhookId = $webhookId;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;
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
