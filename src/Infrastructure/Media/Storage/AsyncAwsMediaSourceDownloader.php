<?php

namespace App\Infrastructure\Media\Storage;

use App\Application\Media\MediaSourceDownloaderInterface;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AsyncAwsMediaSourceDownloader implements MediaSourceDownloaderInterface
{
    public function __construct(
        private S3Client $s3Client,
        #[Autowire('%env(AWS_S3_BUCKET)%')]
        private string $bucket,
    ) {
    }

    #[\Override]
    public function downloadToLocalFile(string $path): string
    {
        $localPath = tempnam(sys_get_temp_dir(), 'media_source_');
        if (false === $localPath) {
            throw new \RuntimeException('Unable to create a local temporary file.');
        }

        $output = $this->s3Client->getObject(new GetObjectRequest(['Bucket' => $this->bucket, 'Key' => $path]));

        $handle = fopen($localPath, 'wb');
        if (false === $handle) {
            throw new \RuntimeException(\sprintf('Unable to open "%s" for writing.', $localPath));
        }

        try {
            foreach ($output->getBody()->getChunks() as $chunk) {
                fwrite($handle, $chunk);
            }
        } finally {
            fclose($handle);
        }

        return $localPath;
    }
}
