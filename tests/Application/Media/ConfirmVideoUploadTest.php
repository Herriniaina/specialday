<?php

namespace App\Tests\Application\Media;

use App\Application\Media\ConfirmVideoUpload;
use App\Application\Media\ConfirmVideoUploadRequest;
use App\Application\Media\FileStorageInterface;
use App\Application\Media\FileTooLargeException;
use App\Application\Media\MediaFileNotFoundException;
use App\Application\Media\MediaObjectMetadata;
use App\Application\Media\MediaObjectMetadataReaderInterface;
use App\Application\Media\MediaObjectNotFoundException;
use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Application\Media\UnsupportedMediaTypeException;
use App\Application\Media\VideoTranscodeSchedulerInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaStatusEnum;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Media\StorageProviderEnum;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class ConfirmVideoUploadTest extends TestCase
{
    private MediaObjectMetadataReaderInterface&MockObject $mediaObjectMetadataReader;
    private FileStorageInterface&MockObject $fileStorage;
    private MediaFileRepositoryInterface&MockObject $mediaFileRepository;
    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;
    private VideoTranscodeSchedulerInterface&MockObject $videoTranscodeScheduler;
    private TransactionInterface&Stub $transaction;
    private ConfirmVideoUpload $confirmVideoUpload;

    protected function setUp(): void
    {
        $this->mediaObjectMetadataReader = $this->createMock(MediaObjectMetadataReaderInterface::class);
        $this->fileStorage = $this->createMock(FileStorageInterface::class);
        $this->mediaFileRepository = $this->createMock(MediaFileRepositoryInterface::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->videoTranscodeScheduler = $this->createMock(VideoTranscodeSchedulerInterface::class);
        $this->transaction = $this->createStub(TransactionInterface::class);
        $this->transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->confirmVideoUpload = new ConfirmVideoUpload(
            $this->mediaObjectMetadataReader,
            $this->fileStorage,
            $this->mediaFileRepository,
            $this->subscriptionRepository,
            $this->videoTranscodeScheduler,
            $this->transaction,
        );
    }

    public function testExecuteConfirmsAVideoAsProcessingAndSchedulesTranscoding(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->with('media-id')->willReturn(null);

        $this->mediaObjectMetadataReader->expects(self::once())
            ->method('read')
            ->with('company/company-id/media/media-id')
            ->willReturn(new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 999));

        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 0, storageQuotaBytes: 10_000),
        );

        $this->mediaFileRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (MediaFile $mediaFile): bool {
                self::assertSame(MediaStatusEnum::PROCESSING, $mediaFile->status);
                self::assertSame('video/mp4', $mediaFile->fileType);
                self::assertSame(999, $mediaFile->fileSize);

                return true;
            }));
        $this->subscriptionRepository->expects(self::once())->method('incrementStorageUsedBytes')->with('company-id', 999);
        $this->videoTranscodeScheduler->expects(self::once())->method('schedule')->with('media-id');
        $this->fileStorage->expects(self::never())->method('delete');

        $mediaFile = $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));

        self::assertSame(MediaStatusEnum::PROCESSING, $mediaFile->status);
    }

    public function testExecuteIsIdempotentAndReturnsTheExistingMediaFile(): void
    {
        $existing = new MediaFile(
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
        );

        $this->mediaFileRepository->expects(self::once())->method('findById')->with('media-id')->willReturn($existing);
        $this->mediaObjectMetadataReader->expects(self::never())->method('read');
        $this->mediaFileRepository->expects(self::never())->method('save');
        $this->subscriptionRepository->expects(self::never())->method('incrementStorageUsedBytes');
        $this->videoTranscodeScheduler->expects(self::never())->method('schedule');

        $result = $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));

        self::assertSame($existing, $result);
    }

    public function testExecuteRejectsAnIdempotentReplayForAnotherCompany(): void
    {
        $existing = new MediaFile(
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
        );

        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn($existing);

        $this->expectException(MediaFileNotFoundException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));
    }

    public function testExecutePropagatesMediaObjectNotFoundWithoutDeleting(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->expects(self::once())->method('read')->willThrowException(
            new MediaObjectNotFoundException('not found'),
        );
        $this->fileStorage->expects(self::never())->method('delete');

        $this->expectException(MediaObjectNotFoundException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));
    }

    public function testExecuteDeletesTheObjectWhenItExceedsTheMaxSizeForItsType(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->expects(self::once())->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 2 * 1024 * 1024 * 1024 + 1),
        );
        $this->fileStorage->expects(self::once())->method('delete')->with('company/company-id/media/media-id');

        $this->expectException(FileTooLargeException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));
    }

    public function testExecuteDeletesTheObjectWhenTheStorageQuotaIsExceeded(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->expects(self::once())->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 1_000),
        );
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 9_500, storageQuotaBytes: 10_000),
        );
        $this->fileStorage->expects(self::once())->method('delete')->with('company/company-id/media/media-id');

        $this->expectException(StorageQuotaExceededException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));
    }

    public function testExecuteDeletesTheObjectWhenNoSubscriptionExists(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->expects(self::once())->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'video/mp4', contentLength: 1_000),
        );
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(null);
        $this->fileStorage->expects(self::once())->method('delete')->with('company/company-id/media/media-id');

        $this->expectException(SubscriptionNotFoundException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));
    }

    public function testExecuteDeletesTheObjectWhenTheRealContentTypeIsUnsupported(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->expects(self::once())->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'application/octet-stream', contentLength: 1_000),
        );
        $this->fileStorage->expects(self::once())->method('delete')->with('company/company-id/media/media-id');

        $this->expectException(UnsupportedMediaTypeException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'clip.mp4',
        ));
    }

    public function testExecuteDeletesTheObjectWhenTheRealContentTypeIsAnImageNotAVideo(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaObjectMetadataReader->expects(self::once())->method('read')->willReturn(
            new MediaObjectMetadata(contentType: 'image/jpeg', contentLength: 1_000),
        );
        $this->fileStorage->expects(self::once())->method('delete')->with('company/company-id/media/media-id');

        $this->expectException(UnsupportedMediaTypeException::class);

        $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
            mediaId: 'media-id',
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'photo.jpg',
        ));
    }
}
