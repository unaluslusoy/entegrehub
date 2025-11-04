<?php

namespace App\Entity;

use App\Repository\UserLabelTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserLabelTemplateRepository::class)]
#[ORM\Table(name: 'user_label_templates')]
#[ORM\HasLifecycleCallbacks]
class UserLabelTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'labelTemplates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Template design configuration stored as JSON
     * Structure: {
     *   "elements": [
     *     {
     *       "type": "qrcode|text|image|barcode|logo",
     *       "x": 10,
     *       "y": 20,
     *       "width": 100,
     *       "height": 100,
     *       "content": "field_name or static_value",
     *       "fontSize": 12,
     *       "fontFamily": "Arial",
     *       "fontWeight": "bold",
     *       "textAlign": "left|center|right",
     *       "color": "#000000",
     *       "backgroundColor": "#ffffff",
     *       "borderWidth": 1,
     *       "borderColor": "#000000",
     *       "rotation": 0
     *     }
     *   ],
     *   "settings": {
     *     "backgroundColor": "#ffffff",
     *     "gridSize": 5,
     *     "showGrid": true
     *   }
     * }
     */
    #[ORM\Column(type: Types::JSON)]
    private array $designConfig = [];

    /**
     * Template dimensions in millimeters
     */
    #[ORM\Column(type: Types::FLOAT)]
    private ?float $width = 100.0; // Default 10cm

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $height = 150.0; // Default 15cm

    /**
     * Paper orientation: portrait or landscape
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $orientation = 'portrait';

    /**
     * Preview image path (auto-generated)
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $previewImage = null;

    /**
     * Is this template active for use?
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    /**
     * Is this the default template for the user?
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDefault = false;

    /**
     * Template category/type
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $category = 'custom'; // custom, thermal, a4, etc.

    /**
     * Number of times this template has been used
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $usageCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    // Lifecycle callbacks
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters and Setters

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDesignConfig(): array
    {
        return $this->designConfig;
    }

    public function setDesignConfig(array $designConfig): static
    {
        $this->designConfig = $designConfig;
        return $this;
    }

    public function getWidth(): ?float
    {
        return $this->width;
    }

    public function setWidth(float $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(float $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getOrientation(): string
    {
        return $this->orientation;
    }

    public function setOrientation(string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function getPreviewImage(): ?string
    {
        return $this->previewImage;
    }

    public function setPreviewImage(?string $previewImage): static
    {
        $this->previewImage = $previewImage;
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

    public function isIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsageCount(): static
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();
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

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    /**
     * Get available field names for template design
     */
    public static function getAvailableFields(): array
    {
        return [
            'tracking' => [
                'label' => 'Takip Numarası',
                'field' => 'shipment.trackingNumber',
                'type' => 'text',
            ],
            'order_number' => [
                'label' => 'Sipariş Numarası',
                'field' => 'order.orderNumber',
                'type' => 'text',
            ],
            'qr_code' => [
                'label' => 'QR Kod',
                'field' => 'qrCode',
                'type' => 'qrcode',
            ],
            'barcode' => [
                'label' => 'Barkod',
                'field' => 'shipment.trackingNumber',
                'type' => 'barcode',
            ],
            'receiver_name' => [
                'label' => 'Alıcı Adı',
                'field' => 'address.firstName + address.lastName',
                'type' => 'text',
            ],
            'receiver_company' => [
                'label' => 'Alıcı Firma',
                'field' => 'address.company',
                'type' => 'text',
            ],
            'receiver_address' => [
                'label' => 'Alıcı Adresi',
                'field' => 'address.address1',
                'type' => 'text',
            ],
            'receiver_city' => [
                'label' => 'Şehir',
                'field' => 'address.city',
                'type' => 'text',
            ],
            'receiver_phone' => [
                'label' => 'Telefon',
                'field' => 'address.phone',
                'type' => 'text',
            ],
            'cargo_company' => [
                'label' => 'Kargo Firması',
                'field' => 'shipment.cargoCompany.name',
                'type' => 'text',
            ],
            'service_type' => [
                'label' => 'Servis Tipi',
                'field' => 'shipment.serviceType',
                'type' => 'text',
            ],
            'weight' => [
                'label' => 'Ağırlık',
                'field' => 'shipment.weight',
                'type' => 'text',
            ],
            'cod_amount' => [
                'label' => 'Kapıda Ödeme',
                'field' => 'shipment.codAmount',
                'type' => 'text',
            ],
            'created_date' => [
                'label' => 'Oluşturma Tarihi',
                'field' => 'shipment.createdAt',
                'type' => 'date',
            ],
            'sender_name' => [
                'label' => 'Gönderici Adı',
                'field' => 'company.firstName + company.lastName',
                'type' => 'text',
            ],
        ];
    }

    /**
     * Get dimensions in points for PDF generation
     */
    public function getDimensionsInPoints(): array
    {
        // 1mm = 2.83465 points
        $pointsPerMm = 2.83465;

        return [
            'width' => $this->width * $pointsPerMm,
            'height' => $this->height * $pointsPerMm,
        ];
    }
}
