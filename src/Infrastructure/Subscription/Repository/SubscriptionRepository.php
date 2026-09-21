<?php

namespace App\Infrastructure\Subscription\Repository;

use App\Domain\Subscription\PaymentMethodEnum;
use App\Domain\Subscription\PlanEnum;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use App\Infrastructure\Subscription\Entity\Subscription as SubscriptionEntity;
use App\Infrastructure\Subscription\Entity\SubscriptionPlan as SubscriptionPlanEntity;
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

        $subscription = new Subscription(
            id: (string) $entity->getId(),
            companyId: $companyId,
            storageUsedBytes: (int) $entity->getStorageUsedBytes(),
            storageQuotaBytes: (int) $subscriptionPlan->getStorage(),
            encodedMinutesUsed: $entity->getEncodedMinutesUsed(),
            encodedMinutesQuota: $subscriptionPlan->getEncodedMinutesQuota(),
            encodedMinutesPeriodStartedAt: $entity->getEncodedMinutesPeriodStartedAt(),
        );

        $this->em->detach($entity);

        return $subscription;
    }

    #[\Override]
    public function existsByCompanyId(string $companyId): bool
    {
        return 0 < $this->em->getRepository(SubscriptionEntity::class)->count(['company' => $companyId]);
    }

    #[\Override]
    public function create(string $companyId, PlanEnum $plan, ?PaymentMethodEnum $paymentMethod, \DateTimeImmutable $startDate): string
    {
        $subscriptionPlan = $this->em->getRepository(SubscriptionPlanEntity::class)->findOneBy(['plan' => $plan]);
        if (null === $subscriptionPlan) {
            throw new \RuntimeException(\sprintf('No subscription plan configured for "%s".', $plan->value));
        }

        $company = $this->em->getReference(CompanyEntity::class, $companyId);
        assert($company instanceof CompanyEntity);

        $entity = new SubscriptionEntity()
            ->setCompany($company)
            ->setSubscriptionPlan($subscriptionPlan)
            ->setPaymentMethod($paymentMethod)
            ->setStartDate($startDate);

        $this->em->persist($entity);
        $this->em->flush();

        return (string) $entity->getId();
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
