<?php

namespace App\Application\Media;

readonly class RequestVideoUploadRequest
{
    public function __construct(
        public string $companyId,
        public string $mimeType,
        public int $fileSize,
    ) {
    }
}
