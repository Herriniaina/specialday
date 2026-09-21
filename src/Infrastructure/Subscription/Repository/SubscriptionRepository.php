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
        $entity = $this->em->createQueryBuilder()
            ->select('s', 'sp')
            ->from(SubscriptionEntity::class, 's')
            ->join('s.subscriptionPlan', 'sp')
            ->where('s.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getOneOrNullResult();
        if (null === $entity) {
            return null;
        }

        assert($entity instanceof SubscriptionEntity);

        $subscriptionPlan = $entity->getSubscriptionPlan();
        assert(null !== $subscriptionPlan);

        return new Subscription(
            id: (string) $entity->getId(),
            companyId: $companyId,
            storageUsedBytes: (int) $entity->getStorageUsedBytes(),
            storageQuotaBytes: (int) $subscriptionPlan->getStorage(),
            encodedMinutesUsed: $entity->getEncodedMinutesUsed(),
            encodedMinutesQuota: $subscriptionPlan->getEncodedMinutesQuota(),
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
