<?php

namespace App\Entity;

use App\Repository\ShipmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShipmentRepository::class)]
#[ORM\Table(name: '`shipments`')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['tracking_number'], name: 'idx_tracking_number')]
#[ORM\Index(columns: ['status'], name: 'idx_shipment_status')]
class Shipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shipments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(inversedBy: 'shipments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CargoCompany $cargoCompany = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cargoKey = null; // Internal cargo company reference

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $status = 'created'; // created, picked_up, in_transit, out_for_delivery, delivered, returned, cancelled

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $actualCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $weight = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $desi = null; // Volumetric weight

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $packageCount = 1;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $serviceType = 'standard'; // standard, express, same_day

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $requiresSignature = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isCOD = false; // Cash on delivery

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $codAmount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $labelUrl = null; // PDF label URL

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $barcodeUrl = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $trackingHistory = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $apiResponse = null; // Last API response

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $estimatedDeliveryDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $pickedUpAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveredAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTrackedAt = null;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
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

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getCargoKey(): ?string
    {
        return $this->cargoKey;
    }

    public function setCargoKey(?string $cargoKey): static
    {
        $this->cargoKey = $cargoKey;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getEstimatedCost(): ?string
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(?string $estimatedCost): static
    {
        $this->estimatedCost = $estimatedCost;
        return $this;
    }

    public function getActualCost(): ?string
    {
        return $this->actualCost;
    }

    public function setActualCost(?string $actualCost): static
    {
        $this->actualCost = $actualCost;
        return $this;
    }

    public function getWeight(): ?string
    {
        return $this->weight;
    }

    public function setWeight(?string $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getDesi(): ?string
    {
        return $this->desi;
    }

    public function setDesi(?string $desi): static
    {
        $this->desi = $desi;
        return $this;
    }

    public function getPackageCount(): ?int
    {
        return $this->packageCount;
    }

    public function setPackageCount(?int $packageCount): static
    {
        $this->packageCount = $packageCount;
        return $this;
    }

    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function setServiceType(?string $serviceType): static
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function requiresSignature(): bool
    {
        return $this->requiresSignature;
    }

    public function setRequiresSignature(bool $requiresSignature): static
    {
        $this->requiresSignature = $requiresSignature;
        return $this;
    }

    public function isCOD(): bool
    {
        return $this->isCOD;
    }

    public function setIsCOD(bool $isCOD): static
    {
        $this->isCOD = $isCOD;
        return $this;
    }

    public function getCodAmount(): ?string
    {
        return $this->codAmount;
    }

    public function setCodAmount(?string $codAmount): static
    {
        $this->codAmount = $codAmount;
        return $this;
    }

    public function getLabelUrl(): ?string
    {
        return $this->labelUrl;
    }

    public function setLabelUrl(?string $labelUrl): static
    {
        $this->labelUrl = $labelUrl;
        return $this;
    }

    public function getBarcodeUrl(): ?string
    {
        return $this->barcodeUrl;
    }

    public function setBarcodeUrl(?string $barcodeUrl): static
    {
        $this->barcodeUrl = $barcodeUrl;
        return $this;
    }

    public function getTrackingHistory(): ?array
    {
        return $this->trackingHistory;
    }

    public function setTrackingHistory(?array $trackingHistory): static
    {
        $this->trackingHistory = $trackingHistory;
        return $this;
    }

    public function addTrackingEvent(array $event): static
    {
        $history = $this->trackingHistory ?? [];
        $event['timestamp'] = new \DateTime();
        $history[] = $event;
        $this->trackingHistory = $history;
        return $this;
    }

    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }

    public function setApiResponse(?array $apiResponse): static
    {
        $this->apiResponse = $apiResponse;
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

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): static
    {
        $this->cancelReason = $cancelReason;
        return $this;
    }

    public function getEstimatedDeliveryDate(): ?\DateTimeInterface
    {
        return $this->estimatedDeliveryDate;
    }

    public function setEstimatedDeliveryDate(?\DateTimeInterface $estimatedDeliveryDate): static
    {
        $this->estimatedDeliveryDate = $estimatedDeliveryDate;
        return $this;
    }

    public function getPickedUpAt(): ?\DateTimeInterface
    {
        return $this->pickedUpAt;
    }

    public function setPickedUpAt(?\DateTimeInterface $pickedUpAt): static
    {
        $this->pickedUpAt = $pickedUpAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeInterface $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }

    public function getLastTrackedAt(): ?\DateTimeInterface
    {
        return $this->lastTrackedAt;
    }

    public function setLastTrackedAt(?\DateTimeInterface $lastTrackedAt): static
    {
        $this->lastTrackedAt = $lastTrackedAt;
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

    public function getTrackingUrl(): string
    {
        if ($this->cargoCompany && $this->trackingNumber) {
            return $this->cargoCompany->getTrackingUrlForNumber($this->trackingNumber);
        }
        return '';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isActive(): bool
    {
        return !in_array($this->status, ['delivered', 'cancelled', 'returned']);
    }
}
