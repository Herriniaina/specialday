<?php

namespace App\Application\Media;

interface VideoUploadUrlGeneratorInterface
{
    public function generate(string $path): string;
}
