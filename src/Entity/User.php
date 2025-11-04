<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(name: 'is_2fa_enabled', type: Types::BOOLEAN)]
    private bool $is2FAEnabled = false;

    #[ORM\Column(name: 'two_factor_secret', length: 255, nullable: true)]
    private ?string $twoFactorSecret = null;

    #[ORM\Column(name: 'reset_token', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_token_expires_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $locale = 'tr';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $appleId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $preferences = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Shop::class)]
    private Collection $shops;

    // DISABLED:     #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    // DISABLED:     #[ORM\JoinTable(name: 'user_roles')]
    // DISABLED:     private Collection $userRoles;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserNotificationConfig::class, cascade: ['persist', 'remove'])]
    private ?UserNotificationConfig $notificationConfig = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserCargoProvider::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $customCargoProviders;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserNotificationTemplate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $notificationTemplates;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserLabelTemplate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $labelTemplates;

    public function __construct()
    {
        $this->shops = new ArrayCollection();
        // $this->userRoles = new ArrayCollection(); // DISABLED
        $this->customCargoProviders = new ArrayCollection();
        $this->notificationTemplates = new ArrayCollection();
        $this->labelTemplates = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary, sensitive data
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
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

    public function is2FAEnabled(): bool
    {
        return $this->is2FAEnabled;
    }

    public function setIs2FAEnabled(bool $is2FAEnabled): static
    {
        $this->is2FAEnabled = $is2FAEnabled;
        return $this;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): static
    {
        $this->twoFactorSecret = $twoFactorSecret;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getAppleId(): ?string
    {
        return $this->appleId;
    }

    public function setAppleId(?string $appleId): static
    {
        $this->appleId = $appleId;
        return $this;
    }

    public function getPreferences(): ?array
    {
        return $this->preferences;
    }

    public function setPreferences(?array $preferences): static
    {
        $this->preferences = $preferences;
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
     * @return Collection<int, Shop>
     */
    public function getShops(): Collection
    {
        return $this->shops;
    }

    public function addShop(Shop $shop): static
    {
        if (!$this->shops->contains($shop)) {
            $this->shops->add($shop);
            $shop->setUser($this);
        }
        return $this;
    }

    public function removeShop(Shop $shop): static
    {
        if ($this->shops->removeElement($shop)) {
            if ($shop->getUser() === $this) {
                $shop->setUser(null);
            }
        }
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    // DISABLED - using roles array instead
    // /**
    //  * @return Collection<int, Role>
    //  */
    // public function getUserRoles(): Collection
    // {
    //     return $this->userRoles;
    // }

    // DISABLED - using roles array instead
    // public function addUserRole(Role $role): static
    // {
    //     if (!$this->userRoles->contains($role)) {
    //         $this->userRoles->add($role);
    //     }
    //     return $this;
    // }

    // DISABLED - using roles array instead
    // public function removeUserRole(Role $role): static
    // {
    //     $this->userRoles->removeElement($role);
    //     return $this;
    // }

    // DISABLED - using roles array instead (userRoles property is disabled)
    // public function hasRole(string $roleSlug): bool
    // {
    //     foreach ($this->userRoles as $role) {
    //         if ($role->getSlug() === $roleSlug) {
    //             return true;
    //         }
    //     }
    //     return in_array($roleSlug, $this->roles);
    // }

    // DISABLED - using roles array instead (userRoles property is disabled)
    // public function hasPermission(string $permissionSlug): bool
    // {
    //     foreach ($this->userRoles as $role) {
    //         if ($role->hasPermission($permissionSlug)) {
    //             return true;
    //         }
    //     }
    //     return false;
    // }

    // DISABLED - using roles array instead (userRoles property is disabled)
    // public function getAllPermissions(): array
    // {
    //     $permissions = [];
    //     foreach ($this->userRoles as $role) {
    //         foreach ($role->getPermissions() as $permission) {
    //             $permissions[$permission->getSlug()] = $permission;
    //         }
    //     }
    //     return array_values($permissions);
    // }

    public function getNotificationConfig(): ?UserNotificationConfig
    {
        return $this->notificationConfig;
    }

    public function setNotificationConfig(?UserNotificationConfig $notificationConfig): static
    {
        // unset the owning side of the relation if necessary
        if ($notificationConfig === null && $this->notificationConfig !== null) {
            $this->notificationConfig->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($notificationConfig !== null && $notificationConfig->getUser() !== $this) {
            $notificationConfig->setUser($this);
        }

        $this->notificationConfig = $notificationConfig;
        return $this;
    }

    /**
     * @return Collection<int, UserCargoProvider>
     */
    public function getCustomCargoProviders(): Collection
    {
        return $this->customCargoProviders;
    }

    public function addCustomCargoProvider(UserCargoProvider $customCargoProvider): static
    {
        if (!$this->customCargoProviders->contains($customCargoProvider)) {
            $this->customCargoProviders->add($customCargoProvider);
            $customCargoProvider->setUser($this);
        }
        return $this;
    }

    public function removeCustomCargoProvider(UserCargoProvider $customCargoProvider): static
    {
        if ($this->customCargoProviders->removeElement($customCargoProvider)) {
            if ($customCargoProvider->getUser() === $this) {
                $customCargoProvider->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, UserNotificationTemplate>
     */
    public function getNotificationTemplates(): Collection
    {
        return $this->notificationTemplates;
    }

    public function addNotificationTemplate(UserNotificationTemplate $notificationTemplate): static
    {
        if (!$this->notificationTemplates->contains($notificationTemplate)) {
            $this->notificationTemplates->add($notificationTemplate);
            $notificationTemplate->setUser($this);
        }
        return $this;
    }

    public function removeNotificationTemplate(UserNotificationTemplate $notificationTemplate): static
    {
        if ($this->notificationTemplates->removeElement($notificationTemplate)) {
            if ($notificationTemplate->getUser() === $this) {
                $notificationTemplate->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, UserLabelTemplate>
     */
    public function getLabelTemplates(): Collection
    {
        return $this->labelTemplates;
    }

    public function addLabelTemplate(UserLabelTemplate $labelTemplate): static
    {
        if (!$this->labelTemplates->contains($labelTemplate)) {
            $this->labelTemplates->add($labelTemplate);
            $labelTemplate->setUser($this);
        }
        return $this;
    }

    public function removeLabelTemplate(UserLabelTemplate $labelTemplate): static
    {
        if ($this->labelTemplates->removeElement($labelTemplate)) {
            if ($labelTemplate->getUser() === $this) {
                $labelTemplate->setUser(null);
            }
        }
        return $this;
    }
}
