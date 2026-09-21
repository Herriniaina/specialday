<?php

namespace App\Application\Media;

interface VideoTranscodeSchedulerInterface
{
    public function schedule(string $mediaFileId): void;
}
