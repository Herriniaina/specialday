<?php

namespace App\Application\Media;

readonly class MediaObjectMetadata
{
    public function __construct(
        public string $contentType,
        public int $contentLength,
    ) {
    }
}
