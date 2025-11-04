<?php

namespace App\Entity;

use App\Repository\CargoProviderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CargoProviderRepository::class)]
#[ORM\Table(name: 'cargo_providers')]
class CargoProvider
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiEndpoint = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webhookUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $configFields = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiDocumentationUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $testModeAvailable = true;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $priority = 0;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'provider', targetEntity: CargoProviderConfig::class, cascade: ['remove'])]
    private Collection $userConfigs;

    public function __construct()
    {
        $this->userConfigs = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(?string $webhookUrl): static
    {
        $this->webhookUrl = $webhookUrl;
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

    public function getApiDocumentationUrl(): ?string
    {
        return $this->apiDocumentationUrl;
    }

    public function setApiDocumentationUrl(?string $apiDocumentationUrl): static
    {
        $this->apiDocumentationUrl = $apiDocumentationUrl;
        return $this;
    }

    public function isTestModeAvailable(): bool
    {
        return $this->testModeAvailable;
    }

    public function setTestModeAvailable(bool $testModeAvailable): static
    {
        $this->testModeAvailable = $testModeAvailable;
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
     * @return Collection<int, CargoProviderConfig>
     */
    public function getUserConfigs(): Collection
    {
        return $this->userConfigs;
    }

    public function addUserConfig(CargoProviderConfig $userConfig): static
    {
        if (!$this->userConfigs->contains($userConfig)) {
            $this->userConfigs->add($userConfig);
            $userConfig->setProvider($this);
        }

        return $this;
    }

    public function removeUserConfig(CargoProviderConfig $userConfig): static
    {
        if ($this->userConfigs->removeElement($userConfig)) {
            if ($userConfig->getProvider() === $this) {
                $userConfig->setProvider(null);
            }
        }

        return $this;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
