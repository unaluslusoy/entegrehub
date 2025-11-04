<?php

namespace App\Entity;

use App\Repository\ShopifySyncLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopifySyncLogRepository::class)]
#[ORM\Table(name: 'shopify_sync_logs')]
#[ORM\Index(name: 'idx_store_id', columns: ['store_id'])]
#[ORM\Index(name: 'idx_sync_type', columns: ['sync_type'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
#[ORM\Index(name: 'idx_started_at', columns: ['started_at'])]
class ShopifySyncLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ShopifyStore::class, inversedBy: 'syncLogs')]
    #[ORM\JoinColumn(name: 'store_id', nullable: false, onDelete: 'CASCADE')]
    private ?ShopifyStore $store = null;

    #[ORM\Column(length: 50)]
    private ?string $syncType = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $recordsTotal = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $recordsSynced = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $recordsFailed = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getSyncType(): ?string
    {
        return $this->syncType;
    }

    public function setSyncType(string $syncType): static
    {
        $this->syncType = $syncType;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRecordsTotal(): int
    {
        return $this->recordsTotal;
    }

    public function setRecordsTotal(int $recordsTotal): static
    {
        $this->recordsTotal = $recordsTotal;
        return $this;
    }

    public function getRecordsSynced(): int
    {
        return $this->recordsSynced;
    }

    public function setRecordsSynced(int $recordsSynced): static
    {
        $this->recordsSynced = $recordsSynced;
        return $this;
    }

    public function getRecordsFailed(): int
    {
        return $this->recordsFailed;
    }

    public function setRecordsFailed(int $recordsFailed): static
    {
        $this->recordsFailed = $recordsFailed;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;
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

    public function getDuration(): ?int
    {
        if ($this->startedAt && $this->completedAt) {
            return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
        }
        return null;
    }

    public function getSuccessRate(): float
    {
        if ($this->recordsTotal === 0) {
            return 0;
        }
        return ($this->recordsSynced / $this->recordsTotal) * 100;
    }
}
