<?php

namespace App\Infrastructure\Subscription\Entity;

use App\Domain\Subscription\PlanEnum;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(length: 20, enumType: PlanEnum::class, unique: true)]
    private ?PlanEnum $plan = null;

    #[ORM\Column(type: 'bigint')]
    private ?int $storage = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $encodedMinutesQuota = 0;

    public function __toString(): string
    {
        return null !== $this->plan ? ucfirst($this->plan->value) : '';
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getPlan(): ?PlanEnum
    {
        return $this->plan;
    }

    public function setPlan(PlanEnum $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getStorage(): ?int
    {
        return $this->storage;
    }

    public function setStorage(int $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getEncodedMinutesQuota(): int
    {
        return $this->encodedMinutesQuota;
    }

    public function setEncodedMinutesQuota(int $encodedMinutesQuota): static
    {
        $this->encodedMinutesQuota = $encodedMinutesQuota;

        return $this;
    }
}
