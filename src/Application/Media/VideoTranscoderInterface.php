<?php

namespace App\Application\Media;

interface VideoTranscoderInterface
{
    public function transcode(string $localSourcePath, string $localOutputPath): VideoTranscodeResult;
}
