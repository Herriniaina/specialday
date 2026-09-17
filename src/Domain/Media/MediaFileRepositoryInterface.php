<?php

namespace App\Domain\Media;

interface MediaFileRepositoryInterface
{
    public function save(MediaFile $mediaFile): void;

    /**
     * @return MediaFile[]
     */
    public function findByCompanyId(string $companyId): array;
}
