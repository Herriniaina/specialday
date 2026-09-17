<?php

namespace App\Application\Media;

interface FileStorageInterface
{
    public function upload(string $localFilePath, string $destinationPath, string $mimeType): void;

    public function delete(string $destinationPath): void;

    /**
     * @return resource
     */
    public function readStream(string $path): mixed;
}
