<?php

namespace App\Application\Media;

readonly class VideoTranscodeResult
{
    public function __construct(
        public int $durationSeconds,
    ) {
    }
}
