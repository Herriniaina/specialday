<?php

namespace App\Domain\Media;

class MediaFile
{
    public function __construct(
        public string $companyId,
        public string $filename,
        public int $fileSize,
        public string $fileType,
        public StorageProviderEnum $storage,
        public string $path,
        public string $createdBy,
        public \DateTimeImmutable $createdAt,
        public ?string $updatedBy = null,
        public ?string $deletedBy = null,
        public ?\DateTimeImmutable $updatedAt = null,
        public ?\DateTimeImmutable $deletedAt = null,
        public ?string $id = null,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
