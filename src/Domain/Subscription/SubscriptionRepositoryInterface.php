<?php

namespace App\Domain\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findByCompanyId(string $companyId): ?Subscription;

    public function incrementStorageUsedBytes(string $companyId, int $bytes): void;

    public function incrementEncodedMinutesUsed(string $companyId, int $minutes, \DateTimeImmutable $now): void;
}
