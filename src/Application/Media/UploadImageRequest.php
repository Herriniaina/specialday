<?php

namespace App\Application\Media;

readonly class UploadImageRequest
{
    public function __construct(
        public string $companyId,
        public string $userId,
        public string $originalFilename,
        public string $mimeType,
        public int $fileSize,
        public string $localFilePath,
    ) {
    }
}
