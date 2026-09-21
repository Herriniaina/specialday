<?php

namespace App\Application\Media;

interface PublicVideoStorageInterface
{
    public function upload(string $localFilePath, string $destinationPath): void;
}
