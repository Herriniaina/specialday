<?php

namespace App\Infrastructure\Subscription\Entity;

use App\Domain\Subscription\PaymentMethodEnum;
use App\Domain\Subscription\PaymentStatusEnum;
use App\Infrastructure\Company\Entity\Company;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_subscription_expiration', columns: ['end_date', 'payment_status'])]
class Subscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private ?UuidInterface $id = null;

    #[ORM\OneToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class)]
    #[ORM\JoinColumn(name: 'subscription_plan_id', referencedColumnName: 'id', nullable: false)]
    private ?SubscriptionPlan $subscriptionPlan = null;

    #[ORM\Column(type: 'bigint', options: ['default' => 0])]
    private ?int $storageUsedBytes = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $encodedMinutesUsed = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $encodedMinutesPeriodStartedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 20, enumType: PaymentMethodEnum::class)]
    private ?PaymentMethodEnum $paymentMethod = null;

    #[ORM\Column(length: 20, enumType: PaymentStatusEnum::class, options: ['default' => 'incomplete'])]
    private PaymentStatusEnum $paymentStatus = PaymentStatusEnum::INCOMPLETE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mobileMoneyReference = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(SubscriptionPlan $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    public function getStorageUsedBytes(): ?int
    {
        return $this->storageUsedBytes;
    }

    public function setStorageUsedBytes(int $storageUsedBytes): static
    {
        $this->storageUsedBytes = $storageUsedBytes;

        return $this;
    }

    public function getEncodedMinutesUsed(): int
    {
        return $this->encodedMinutesUsed;
    }

    public function setEncodedMinutesUsed(int $encodedMinutesUsed): static
    {
        $this->encodedMinutesUsed = $encodedMinutesUsed;

        return $this;
    }

    public function getEncodedMinutesPeriodStartedAt(): ?\DateTimeImmutable
    {
        return $this->encodedMinutesPeriodStartedAt;
    }

    public function setEncodedMinutesPeriodStartedAt(?\DateTimeImmutable $encodedMinutesPeriodStartedAt): static
    {
        $this->encodedMinutesPeriodStartedAt = $encodedMinutesPeriodStartedAt;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethodEnum
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethodEnum $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentStatus(): PaymentStatusEnum
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatusEnum $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function getMobileMoneyReference(): ?string
    {
        return $this->mobileMoneyReference;
    }

    public function setMobileMoneyReference(?string $mobileMoneyReference): static
    {
        $this->mobileMoneyReference = $mobileMoneyReference;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
