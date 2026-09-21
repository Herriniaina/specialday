<?php

namespace App\Application\Media;

readonly class TranscodeVideoRequest
{
    public function __construct(
        public string $mediaFileId,
    ) {
    }
}
