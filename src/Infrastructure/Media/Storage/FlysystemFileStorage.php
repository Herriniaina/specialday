<?php

namespace App\Infrastructure\Media\Storage;

use App\Application\Media\FileStorageInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class FlysystemFileStorage implements FileStorageInterface
{
    public function __construct(
        #[Autowire(service: 'media.storage')]
        private FilesystemOperator $storage,
    ) {
    }

    #[\Override]
    public function upload(string $localFilePath, string $destinationPath, string $mimeType): void
    {
        $stream = fopen($localFilePath, 'r');
        if (false === $stream) {
            throw new \RuntimeException(\sprintf('Unable to open "%s" for upload.', $localFilePath));
        }

        try {
            $this->storage->writeStream($destinationPath, $stream, ['ContentType' => $mimeType]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    #[\Override]
    public function delete(string $destinationPath): void
    {
        $this->storage->delete($destinationPath);
    }
}
