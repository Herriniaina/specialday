<?php

namespace App\Tests\Infrastructure\Media\Http;

use App\Application\Media\ConfirmVideoUpload;
use App\Application\Media\FileStorageInterface;
use App\Application\Media\MediaObjectMetadata;
use App\Application\Media\MediaObjectMetadataReaderInterface;
use App\Application\Media\MediaObjectNotFoundException;
use App\Application\Media\VideoTranscodeSchedulerInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaStatusEnum;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Media\StorageProviderEnum;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Infrastructure\Company\Entity\Company;
use App\Infrastructure\Media\Http\ConfirmVideoUploadController;
use App\Infrastructure\Media\Http\ConfirmVideoUploadInput;
use App\Infrastructure\User\Entity\User;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ConfirmVideoUploadControllerTest extends TestCase
{
    private MediaObjectMetadataReaderInterface&Stub $mediaObjectMetadataReader;
    private FileStorageInterface&Stub $fileStorage;
    private MediaFileRepositoryInterface&Stub $mediaFileRepository;
    private SubscriptionRepositoryInterface&Stub $subscriptionRepository;
    private VideoTranscodeSchedulerInterface&Stub $videoTranscodeScheduler;
    private ConfirmVideoUploadController $controller;

    protected function setUp(): void
    {
        $this->mediaObjectMetadataReader = $this->createStub(MediaObjectMetadataReaderInterface::class);
        $this->fileStorage = $this->createStub(FileStorageInterface::class);
        $this->mediaFileRepository = $this->createStub(MediaFileRepositoryInterface::class);
        $this->subscriptionRepository = $this->createStub(SubscriptionRepositoryInterface::class);
        $this->videoTranscodeScheduler = $this->createStub(VideoTranscodeSchedulerInterface::class);
        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->controller = new ConfirmVideoUploadController(new ConfirmVideoUpload(
            $this->mediaObjectMetadataReader,
            $this->fileStorage,
            $this->mediaFileRepository,
            $this->subscriptionRepository,
            $this->videoTranscodeScheduler,
            $transaction,
        ));
    }

    private function input(): ConfirmVideoUploadInput
    {
        return new ConfirmVideoUploadInput(filename: 'clip.mp4');
    }

    private function companyAdmin(): User
    {
        return (new User())->setCompany((new Company())->setId('company-id'));
    }

    /**
     * @return array<string, mixed>
     */
    private function body(JsonResponse $response): array
    {
        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);

        return $body;
    }

    public function testReturnsForbiddenWhenUserHasNoCompany(): void
    {
        $response = ($this->controller)('media-id', $this->input(), new User());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('media.company.required', $this->body($response)['statusText']);
    }

    public function testReturnsNotFoundWhenTheMediaFileBelongsToAnotherCompany(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(new MediaFile(
            companyId: 'other-company-id',
            filename: 'clip.mp4',
            fileSize: 999,
            fileType: 'video/mp4',
            mediaType: MediaTypeEnum::VIDEO,
            storage: StorageProviderEnum::S3,
            path: 'company/other-company-id/media/media-id',
            createdBy: 'user-id',
            createdAt: new \DateTimeImmutable(),
            id: 'media-id',
        ));

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testReturnsNotFoundWhenTheMediaObjectIsMissingFromStorage(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->method('read')->willThrowException(new MediaObjectNotFoundException('not found'));

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testReturnsUnsupportedMediaTypeWhenTheUploadedFileIsNotAVideo(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'image/jpeg', contentLength: 1_000),
        );

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE, $response->getStatusCode());
    }

    public function testReturnsForbiddenWhenTheCompanyHasNoSubscription(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 1_000),
        );
        $this->subscriptionRepository->method('findByCompanyId')->willReturn(null);

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testReturnsPayloadTooLargeWhenTheFileExceedsTheMaxSize(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 2 * 1024 * 1024 * 1024 + 1),
        );

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $response->getStatusCode());
    }

    public function testReturnsServerErrorOnAnUnexpectedException(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->method('read')->willThrowException(new \RuntimeException('storage down'));

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testReturnsAcceptedWhenTheVideoStartsProcessing(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 1_000),
        );
        $this->subscriptionRepository->method('findByCompanyId')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 0, storageQuotaBytes: 10_000),
        );

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        self::assertSame('processing', $this->body($response)['payload']['status']);
    }

    public function testReturnsCreatedWhenReplayingAnAlreadyProcessedUpload(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(new MediaFile(
            companyId: 'company-id',
            filename: 'clip.mp4',
            fileSize: 999,
            fileType: 'video/mp4',
            mediaType: MediaTypeEnum::VIDEO,
            storage: StorageProviderEnum::S3,
            path: 'company/company-id/media/media-id',
            createdBy: 'user-id',
            createdAt: new \DateTimeImmutable(),
            id: 'media-id',
            status: MediaStatusEnum::READY,
        ));

        $response = ($this->controller)('media-id', $this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('ready', $this->body($response)['payload']['status']);
    }
}
