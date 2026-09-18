<?php

namespace App\Application\Media;

readonly class ConfirmVideoUploadRequest
{
    public function __construct(
        public string $mediaId,
        public string $companyId,
        public string $userId,
        public string $originalFilename,
    ) {
    }
}
