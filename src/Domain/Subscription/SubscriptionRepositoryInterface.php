<?php

namespace App\Domain\Subscription;

interface SubscriptionRepositoryInterface
{
    public function findByCompanyId(string $companyId): ?Subscription;

    public function incrementStorageUsedBytes(string $companyId, int $bytes): void;
}
