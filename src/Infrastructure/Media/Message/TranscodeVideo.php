<?php

namespace App\Infrastructure\Media\Message;

readonly class TranscodeVideo
{
    public function __construct(
        public string $mediaFileId,
    ) {
    }
}
