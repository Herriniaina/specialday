<?php

namespace App\Infrastructure\Subscription\Repository;

use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Infrastructure\Subscription\Entity\Subscription as SubscriptionEntity;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function findByCompanyId(string $companyId): ?Subscription
    {
        $entity = $this->em->getRepository(SubscriptionEntity::class)->findOneBy(['company' => $companyId]);
        if (null === $entity) {
            return null;
        }

        $company = $entity->getCompany();
        $plan = $entity->getSubscriptionPlan();
        \assert(null !== $company && null !== $plan);

        return new Subscription(
            id: (string) $entity->getId(),
            companyId: (string) $company->getId(),
            storageUsedBytes: (int) $entity->getStorageUsedBytes(),
            storageQuotaBytes: (int) $plan->getStorage(),
            encodedMinutesUsed: $entity->getEncodedMinutesUsed(),
            encodedMinutesQuota: $plan->getEncodedMinutesQuota(),
            encodedMinutesPeriodStartedAt: $entity->getEncodedMinutesPeriodStartedAt(),
        );
    }

    #[\Override]
    public function incrementStorageUsedBytes(string $companyId, int $bytes): void
    {
        $entity = $this->em->getRepository(SubscriptionEntity::class)->findOneBy(['company' => $companyId]);
        if (null === $entity) {
            return;
        }

        $entity->setStorageUsedBytes((int) $entity->getStorageUsedBytes() + $bytes);
        $this->em->flush();
    }

    #[\Override]
    public function incrementEncodedMinutesUsed(string $companyId, int $minutes, \DateTimeImmutable $now): void
    {
        $entity = $this->em->getRepository(SubscriptionEntity::class)->findOneBy(['company' => $companyId]);
        if (null === $entity) {
            return;
        }

        $periodStartedAt = $entity->getEncodedMinutesPeriodStartedAt();
        $isSamePeriod = null !== $periodStartedAt && $periodStartedAt->format('Y-m') === $now->format('Y-m');

        $entity
            ->setEncodedMinutesUsed(($isSamePeriod ? $entity->getEncodedMinutesUsed() : 0) + $minutes)
            ->setEncodedMinutesPeriodStartedAt($isSamePeriod ? $periodStartedAt : $now);

        $this->em->flush();
    }
}
