<?php

namespace App\Infrastructure\Media\Storage;

use App\Application\Media\PublicVideoStorageInterface;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AsyncAwsPublicVideoStorage implements PublicVideoStorageInterface
{
    public function __construct(
        private S3Client $s3Client,
        #[Autowire('%env(AWS_S3_PUBLIC_BUCKET)%')]
        private string $bucket,
    ) {
    }

    #[\Override]
    public function upload(string $localFilePath, string $destinationPath): void
    {
        $stream = fopen($localFilePath, 'r');
        if (false === $stream) {
            throw new \RuntimeException(\sprintf('Unable to open "%s" for upload.', $localFilePath));
        }

        try {
            $this->s3Client->putObject(new PutObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $destinationPath,
                'Body' => $stream,
                'ContentType' => 'video/mp4',
            ]));
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
