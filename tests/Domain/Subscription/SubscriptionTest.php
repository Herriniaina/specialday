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
}
