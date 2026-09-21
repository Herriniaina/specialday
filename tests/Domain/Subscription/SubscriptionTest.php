<?php

namespace App\Tests\Domain\Subscription;

use App\Domain\Subscription\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function testHasStorageForReturnsTrueWhenWithinQuota(): void
    {
        $subscription = new Subscription(id: 'sub-id', companyId: 'company-id', storageUsedBytes: 1_000, storageQuotaBytes: 10_000);

        self::assertTrue($subscription->hasStorageFor(5_000));
    }

    public function testHasStorageForReturnsTrueWhenExactlyAtQuota(): void
    {
        $subscription = new Subscription(id: 'sub-id', companyId: 'company-id', storageUsedBytes: 1_000, storageQuotaBytes: 10_000);

        self::assertTrue($subscription->hasStorageFor(9_000));
    }

    public function testHasStorageForReturnsFalseWhenExceedingQuota(): void
    {
        $subscription = new Subscription(id: 'sub-id', companyId: 'company-id', storageUsedBytes: 9_500, storageQuotaBytes: 10_000);

        self::assertFalse($subscription->hasStorageFor(1_000));
    }

    public function testHasEncodingMinutesForReturnsTrueWithinQuotaForTheCurrentPeriod(): void
    {
        $now = new \DateTimeImmutable('2026-09-18');
        $subscription = new Subscription(
            id: 'sub-id',
            companyId: 'company-id',
            storageUsedBytes: 0,
            storageQuotaBytes: 10_000,
            encodedMinutesUsed: 50,
            encodedMinutesQuota: 100,
            encodedMinutesPeriodStartedAt: new \DateTimeImmutable('2026-09-01'),
        );

        self::assertTrue($subscription->hasEncodingMinutesFor(50, $now));
        self::assertFalse($subscription->hasEncodingMinutesFor(51, $now));
    }

    public function testHasEncodingMinutesForIgnoresUsageFromAPreviousPeriod(): void
    {
        $now = new \DateTimeImmutable('2026-09-18');
        $subscription = new Subscription(
            id: 'sub-id',
            companyId: 'company-id',
            storageUsedBytes: 0,
            storageQuotaBytes: 10_000,
            encodedMinutesUsed: 100,
            encodedMinutesQuota: 100,
            encodedMinutesPeriodStartedAt: new \DateTimeImmutable('2026-08-15'),
        );

        self::assertTrue($subscription->hasEncodingMinutesFor(100, $now));
    }

    public function testHasEncodingMinutesForTreatsANullPeriodAsNoUsage(): void
    {
        $subscription = new Subscription(
            id: 'sub-id',
            companyId: 'company-id',
            storageUsedBytes: 0,
            storageQuotaBytes: 10_000,
            encodedMinutesUsed: 0,
            encodedMinutesQuota: 30,
        );

        self::assertTrue($subscription->hasEncodingMinutesFor(30, new \DateTimeImmutable()));
    }
}
