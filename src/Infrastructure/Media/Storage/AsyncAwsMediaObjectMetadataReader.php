<?php

namespace App\Infrastructure\Media\Storage;

use App\Application\Media\MediaObjectMetadata;
use App\Application\Media\MediaObjectMetadataReaderInterface;
use App\Application\Media\MediaObjectNotFoundException;
use AsyncAws\Core\Exception\Http\ClientException;
use AsyncAws\S3\Input\HeadObjectRequest;
use AsyncAws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AsyncAwsMediaObjectMetadataReader implements MediaObjectMetadataReaderInterface
{
    public function __construct(
        private S3Client $s3Client,
        #[Autowire('%env(AWS_S3_BUCKET)%')]
        private string $bucket,
    ) {
    }

    #[\Override]
    public function read(string $path): MediaObjectMetadata
    {
        try {
            $output = $this->s3Client->headObject(new HeadObjectRequest(['Bucket' => $this->bucket, 'Key' => $path]));

            return new MediaObjectMetadata(
                contentType: $output->getContentType() ?? 'application/octet-stream',
                contentLength: $output->getContentLength() ?? 0,
            );
        } catch (ClientException $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                throw new MediaObjectNotFoundException(\sprintf('No object uploaded at "%s".', $path));
            }

            throw $e;
        }
    }
}
