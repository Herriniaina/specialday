<?php

namespace App\Domain\Subscription;

class Subscription
{
    public function __construct(
        public string $id,
        public string $companyId,
        public int $storageUsedBytes,
        public int $storageQuotaBytes,
    ) {
    }

    public function hasStorageFor(int $additionalBytes): bool
    {
        return $this->storageUsedBytes + $additionalBytes <= $this->storageQuotaBytes;
    }
}
