<?php

namespace App\Entity;

use App\Repository\UserSubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Kullanıcının aktif abonelik bilgileri
 */
#[ORM\Entity(repositoryClass: UserSubscriptionRepository::class)]
#[ORM\Table(name: '`user_subscriptions`')]
#[ORM\HasLifecycleCallbacks]
class UserSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    // #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true)]
    // private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'active'; // active, expired, cancelled, suspended

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $billingPeriod = 'monthly'; // monthly, yearly

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextBillingDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $suspendedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationReason = null;

    // Kullanım istatistikleri (bu ay)
    #[ORM\Column(type: Types::INTEGER)]
    private int $currentMonthOrders = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $currentMonthSms = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $currentMonthEmails = 0;

    // Ödeme bilgileri
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $paymentMethod = null; // credit_card, bank_transfer, paypal

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $paymentGatewayId = null; // Stripe/Iyzico customer ID

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $subscriptionGatewayId = null; // Stripe subscription ID

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $autoRenew = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastPaymentDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $lastPaymentAmount = null;

    // Trial period
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isTrialPeriod = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $trialStartDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $trialEndDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->startDate = new \DateTime();
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

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;
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

    public function getBillingPeriod(): string
    {
        return $this->billingPeriod;
    }

    public function setBillingPeriod(string $billingPeriod): static
    {
        $this->billingPeriod = $billingPeriod;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getNextBillingDate(): ?\DateTimeInterface
    {
        return $this->nextBillingDate;
    }

    public function setNextBillingDate(?\DateTimeInterface $nextBillingDate): static
    {
        $this->nextBillingDate = $nextBillingDate;
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

    public function getSuspendedAt(): ?\DateTimeInterface
    {
        return $this->suspendedAt;
    }

    public function setSuspendedAt(?\DateTimeInterface $suspendedAt): static
    {
        $this->suspendedAt = $suspendedAt;
        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;
        return $this;
    }

    public function getCurrentMonthOrders(): int
    {
        return $this->currentMonthOrders;
    }

    public function setCurrentMonthOrders(int $currentMonthOrders): static
    {
        $this->currentMonthOrders = $currentMonthOrders;
        return $this;
    }

    public function incrementOrders(int $count = 1): static
    {
        $this->currentMonthOrders += $count;
        return $this;
    }

    public function getCurrentMonthSms(): int
    {
        return $this->currentMonthSms;
    }

    public function setCurrentMonthSms(int $currentMonthSms): static
    {
        $this->currentMonthSms = $currentMonthSms;
        return $this;
    }

    public function incrementSms(int $count = 1): static
    {
        $this->currentMonthSms += $count;
        return $this;
    }

    public function getCurrentMonthEmails(): int
    {
        return $this->currentMonthEmails;
    }

    public function setCurrentMonthEmails(int $currentMonthEmails): static
    {
        $this->currentMonthEmails = $currentMonthEmails;
        return $this;
    }

    public function incrementEmails(int $count = 1): static
    {
        $this->currentMonthEmails += $count;
        return $this;
    }

    public function resetMonthlyUsage(): static
    {
        $this->currentMonthOrders = 0;
        $this->currentMonthSms = 0;
        $this->currentMonthEmails = 0;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentGatewayId(): ?string
    {
        return $this->paymentGatewayId;
    }

    public function setPaymentGatewayId(?string $paymentGatewayId): static
    {
        $this->paymentGatewayId = $paymentGatewayId;
        return $this;
    }

    public function getSubscriptionGatewayId(): ?string
    {
        return $this->subscriptionGatewayId;
    }

    public function setSubscriptionGatewayId(?string $subscriptionGatewayId): static
    {
        $this->subscriptionGatewayId = $subscriptionGatewayId;
        return $this;
    }

    public function isAutoRenew(): bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew): static
    {
        $this->autoRenew = $autoRenew;
        return $this;
    }

    public function getLastPaymentDate(): ?\DateTimeInterface
    {
        return $this->lastPaymentDate;
    }

    public function setLastPaymentDate(?\DateTimeInterface $lastPaymentDate): static
    {
        $this->lastPaymentDate = $lastPaymentDate;
        return $this;
    }

    public function getLastPaymentAmount(): ?string
    {
        return $this->lastPaymentAmount;
    }

    public function setLastPaymentAmount(?string $lastPaymentAmount): static
    {
        $this->lastPaymentAmount = $lastPaymentAmount;
        return $this;
    }

    public function isTrialPeriod(): bool
    {
        return $this->isTrialPeriod;
    }

    public function setIsTrialPeriod(bool $isTrialPeriod): static
    {
        $this->isTrialPeriod = $isTrialPeriod;
        return $this;
    }

    public function getTrialStartDate(): ?\DateTimeInterface
    {
        return $this->trialStartDate;
    }

    public function setTrialStartDate(?\DateTimeInterface $trialStartDate): static
    {
        $this->trialStartDate = $trialStartDate;
        return $this;
    }

    public function getTrialEndDate(): ?\DateTimeInterface
    {
        return $this->trialEndDate;
    }

    public function setTrialEndDate(?\DateTimeInterface $trialEndDate): static
    {
        $this->trialEndDate = $trialEndDate;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        if (!$this->endDate) {
            return false;
        }
        return $this->endDate < new \DateTime();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasOrderQuotaRemaining(): bool
    {
        if (!$this->plan) {
            return false;
        }
        return $this->currentMonthOrders < $this->plan->getMaxOrders();
    }

    public function hasSmsQuotaRemaining(): bool
    {
        if (!$this->plan || !$this->plan->getMaxSmsPerMonth()) {
            return true; // Unlimited
        }
        return $this->currentMonthSms < $this->plan->getMaxSmsPerMonth();
    }

    public function hasEmailQuotaRemaining(): bool
    {
        if (!$this->plan || !$this->plan->getMaxEmailPerMonth()) {
            return true; // Unlimited
        }
        return $this->currentMonthEmails < $this->plan->getMaxEmailPerMonth();
    }

    public function getDaysRemaining(): int
    {
        if (!$this->endDate) {
            return 0;
        }
        $now = new \DateTime();
        $diff = $now->diff($this->endDate);
        return $diff->days * ($diff->invert ? -1 : 1);
    }

    public function getUsagePercentage(string $type = 'orders'): float
    {
        if (!$this->plan) {
            return 0;
        }

        return match($type) {
            'orders' => $this->plan->getMaxOrders() > 0 
                ? ($this->currentMonthOrders / $this->plan->getMaxOrders()) * 100 
                : 0,
            'sms' => $this->plan->getMaxSmsPerMonth() > 0 
                ? ($this->currentMonthSms / $this->plan->getMaxSmsPerMonth()) * 100 
                : 0,
            'emails' => $this->plan->getMaxEmailPerMonth() > 0 
                ? ($this->currentMonthEmails / $this->plan->getMaxEmailPerMonth()) * 100 
                : 0,
            default => 0,
        };
    }
}
