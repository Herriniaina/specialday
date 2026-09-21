<?php

namespace App\Domain\Subscription;

class Subscription
{
    public function __construct(
        public string $id,
        public string $companyId,
        public int $storageUsedBytes,
        public int $storageQuotaBytes,
        public int $encodedMinutesUsed = 0,
        public int $encodedMinutesQuota = 0,
        public ?\DateTimeImmutable $encodedMinutesPeriodStartedAt = null,
    ) {
    }

    public function hasStorageFor(int $additionalBytes): bool
    {
        return $this->storageUsedBytes + $additionalBytes <= $this->storageQuotaBytes;
    }

    public function hasEncodingMinutesFor(int $additionalMinutes, \DateTimeImmutable $now): bool
    {
        $used = $this->isCurrentEncodingPeriod($now) ? $this->encodedMinutesUsed : 0;

        return $used + $additionalMinutes <= $this->encodedMinutesQuota;
    }

    public function isCurrentEncodingPeriod(\DateTimeImmutable $now): bool
    {
        return null !== $this->encodedMinutesPeriodStartedAt
            && $this->encodedMinutesPeriodStartedAt->format('Y-m') === $now->format('Y-m');
    }
}
