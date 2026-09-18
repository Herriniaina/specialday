<?php

namespace App\Application\Media;

interface MediaObjectMetadataReaderInterface
{
    /**
     * @throws MediaObjectNotFoundException when no object exists at $path
     */
    public function read(string $path): MediaObjectMetadata;
}
