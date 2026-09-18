<?php

namespace App\Tests\Application\Media;

use App\Application\Media\FileStorageInterface;
use App\Application\Media\MediaSourceDownloaderInterface;
use App\Application\Media\PublicVideoStorageInterface;
use App\Application\Media\TranscodeVideo;
use App\Application\Media\TranscodeVideoRequest;
use App\Application\Media\VideoTranscodeResult;
use App\Application\Media\VideoTranscoderInterface;
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

class TranscodeVideoTest extends TestCase
{
    private MediaFileRepositoryInterface&MockObject $mediaFileRepository;
    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;
    private MediaSourceDownloaderInterface&MockObject $mediaSourceDownloader;
    private PublicVideoStorageInterface&MockObject $publicVideoStorage;
    private VideoTranscoderInterface&MockObject $videoTranscoder;
    private FileStorageInterface&MockObject $fileStorage;
    private TransactionInterface&Stub $transaction;
    private TranscodeVideo $transcodeVideo;

    protected function setUp(): void
    {
        $this->mediaFileRepository = $this->createMock(MediaFileRepositoryInterface::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->mediaSourceDownloader = $this->createMock(MediaSourceDownloaderInterface::class);
        $this->publicVideoStorage = $this->createMock(PublicVideoStorageInterface::class);
        $this->videoTranscoder = $this->createMock(VideoTranscoderInterface::class);
        $this->fileStorage = $this->createMock(FileStorageInterface::class);
        $this->transaction = $this->createStub(TransactionInterface::class);
        $this->transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->transcodeVideo = new TranscodeVideo(
            $this->mediaFileRepository,
            $this->subscriptionRepository,
            $this->mediaSourceDownloader,
            $this->publicVideoStorage,
            $this->videoTranscoder,
            $this->fileStorage,
            $this->transaction,
        );
    }

    private function processingVideo(): MediaFile
    {
        return new MediaFile(
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
            status: MediaStatusEnum::PROCESSING,
        );
    }

    public function testExecuteTranscodesAndPublishesTheVideo(): void
    {
        $mediaFile = $this->processingVideo();
        $this->mediaFileRepository->expects(self::once())->method('findById')->with('media-id')->willReturn($mediaFile);

        $this->mediaSourceDownloader->expects(self::once())
            ->method('downloadToLocalFile')
            ->with('company/company-id/media/media-id')
            ->willReturn('/tmp/local-source');

        $this->videoTranscoder->expects(self::once())
            ->method('transcode')
            ->with('/tmp/local-source', '/tmp/local-source.out.mp4')
            ->willReturn(new VideoTranscodeResult(durationSeconds: 90));

        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(
            new Subscription(id: 'sub-id', companyId: 'company-id', storageUsedBytes: 0, storageQuotaBytes: 10_000, encodedMinutesUsed: 0, encodedMinutesQuota: 100),
        );

        $this->publicVideoStorage->expects(self::once())
            ->method('upload')
            ->with('/tmp/local-source.out.mp4', 'company/company-id/media/media-id');

        $this->mediaFileRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (MediaFile $updated): bool {
                self::assertSame(MediaStatusEnum::READY, $updated->status);
                self::assertSame(90, $updated->durationSeconds);
                self::assertSame('company/company-id/media/media-id', $updated->path);

                return true;
            }));

        $this->subscriptionRepository->expects(self::once())->method('incrementEncodedMinutesUsed')->with('company-id', 2, self::isInstanceOf(\DateTimeImmutable::class));

        $this->fileStorage->expects(self::once())->method('delete')->with('company/company-id/media/media-id');

        $this->transcodeVideo->execute(new TranscodeVideoRequest('media-id'));
    }

    public function testExecuteDoesNothingWhenTheMediaFileDoesNotExist(): void
    {
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mediaSourceDownloader->expects(self::never())->method('downloadToLocalFile');

        $this->transcodeVideo->execute(new TranscodeVideoRequest('media-id'));
    }

    public function testExecuteDoesNothingWhenTheMediaFileIsNotProcessing(): void
    {
        $mediaFile = new MediaFile(
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
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn($mediaFile);
        $this->mediaSourceDownloader->expects(self::never())->method('downloadToLocalFile');

        $this->transcodeVideo->execute(new TranscodeVideoRequest('media-id'));
    }

    public function testExecuteMarksTheMediaFileAsFailedWhenTheEncodingQuotaIsExceeded(): void
    {
        $mediaFile = $this->processingVideo();
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn($mediaFile);
        $this->mediaSourceDownloader->expects(self::once())->method('downloadToLocalFile')->willReturn('/tmp/local-source');
        $this->videoTranscoder->expects(self::once())->method('transcode')->willReturn(new VideoTranscodeResult(durationSeconds: 90));

        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(
            new Subscription(
                id: 'sub-id',
                companyId: 'company-id',
                storageUsedBytes: 0,
                storageQuotaBytes: 10_000,
                encodedMinutesUsed: 100,
                encodedMinutesQuota: 100,
                encodedMinutesPeriodStartedAt: new \DateTimeImmutable(),
            ),
        );

        $this->publicVideoStorage->expects(self::never())->method('upload');
        $this->subscriptionRepository->expects(self::never())->method('incrementEncodedMinutesUsed');
        $this->fileStorage->expects(self::never())->method('delete');

        $this->mediaFileRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(fn (MediaFile $updated): bool => MediaStatusEnum::FAILED === $updated->status));

        $this->transcodeVideo->execute(new TranscodeVideoRequest('media-id'));
    }

    public function testExecuteMarksTheMediaFileAsFailedAndRethrowsWhenTranscodingFails(): void
    {
        $mediaFile = $this->processingVideo();
        $this->mediaFileRepository->expects(self::once())->method('findById')->willReturn($mediaFile);
        $this->mediaSourceDownloader->expects(self::once())->method('downloadToLocalFile')->willReturn('/tmp/local-source');
        $this->videoTranscoder->expects(self::once())->method('transcode')->willThrowException(new \RuntimeException('ffmpeg failed'));

        $this->mediaFileRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(fn (MediaFile $updated): bool => MediaStatusEnum::FAILED === $updated->status));

        $this->expectException(\RuntimeException::class);

        $this->transcodeVideo->execute(new TranscodeVideoRequest('media-id'));
    }
}
