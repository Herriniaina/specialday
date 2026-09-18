<?php

namespace App\Application\Media;

interface MediaSourceDownloaderInterface
{
    /**
     * Downloads the object at $path to a local temporary file and returns its path.
     */
    public function downloadToLocalFile(string $path): string;
}
