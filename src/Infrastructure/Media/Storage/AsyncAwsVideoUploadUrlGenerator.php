<?php

namespace App\Infrastructure\Media\Storage;

use App\Application\Media\VideoUploadUrlGeneratorInterface;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AsyncAwsVideoUploadUrlGenerator implements VideoUploadUrlGeneratorInterface
{
    private const UPLOAD_URL_TTL_MINUTES = 30;

    public function __construct(
        #[Autowire(service: 'media.presign_s3_client')]
        private S3Client $s3Client,
        #[Autowire('%env(AWS_S3_BUCKET)%')]
        private string $bucket,
    ) {
    }

    #[\Override]
    public function generate(string $path): string
    {
        return $this->s3Client->presign(
            new PutObjectRequest(['Bucket' => $this->bucket, 'Key' => $path]),
            (new \DateTimeImmutable())->modify('+'.self::UPLOAD_URL_TTL_MINUTES.' minutes'),
        );
    }
}
