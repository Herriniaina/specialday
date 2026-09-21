<?php

namespace App\Tests\Application\Media;

use App\Application\Media\FileStorageInterface;
use App\Application\Media\FileTooLargeException;
use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Application\Media\UploadImage;
use App\Application\Media\UploadImageRequest;
use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Media\StorageProviderEnum;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class UploadImageTest extends TestCase
{
    private FileStorageInterface&MockObject $fileStorage;
    private MediaFileRepositoryInterface&MockObject $mediaFileRepository;
    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;
    private TransactionInterface&Stub $transaction;
    private IdGeneratorInterface&Stub $idGenerator;
    private UploadImage $uploadImage;

    protected function setUp(): void
    {
        $this->fileStorage = $this->createMock(FileStorageInterface::class);
        $this->mediaFileRepository = $this->createMock(MediaFileRepositoryInterface::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->transaction = $this->createStub(TransactionInterface::class);
        $this->transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());
        $this->idGenerator = $this->createStub(IdGeneratorInterface::class);
        $this->idGenerator->method('generate')->willReturn('generated-id');

        $this->uploadImage = new UploadImage($this->fileStorage, $this->mediaFileRepository, $this->subscriptionRepository, $this->transaction, $this->idGenerator);
    }

    public function testExecuteUploadsThenSavesTheMediaFileAndIncrementsStorageUsage(): void
    {
        $request = new UploadImageRequest(
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'photo.jpg',
            mimeType: 'image/jpeg',
            fileSize: 1234,
            localFilePath: '/tmp/photo.jpg',
        );

        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->with('company-id')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 1_000, storageQuotaBytes: 10_000),
        );

        $this->fileStorage->expects(self::once())
            ->method('upload')
            ->with('/tmp/photo.jpg', 'company/company-id/media/generated-id', 'image/jpeg');

        $this->mediaFileRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (MediaFile $mediaFile): bool {
                self::assertSame('generated-id', $mediaFile->getId());
                self::assertSame('company-id', $mediaFile->companyId);
                self::assertSame('user-id', $mediaFile->createdBy);
                self::assertSame('photo.jpg', $mediaFile->filename);
                self::assertSame(1234, $mediaFile->fileSize);
                self::assertSame('image/jpeg', $mediaFile->fileType);
                self::assertSame(MediaTypeEnum::IMAGE, $mediaFile->mediaType);
                self::assertSame(StorageProviderEnum::S3, $mediaFile->storage);
                self::assertSame('company/company-id/media/generated-id', $mediaFile->path);

                return true;
            }));

        $this->subscriptionRepository->expects(self::once())
            ->method('incrementStorageUsedBytes')
            ->with('company-id', 1234);

        $mediaFile = $this->uploadImage->execute($request);

        self::assertSame('generated-id', $mediaFile->getId());
    }

    public function testExecuteDeletesTheUploadedFileWhenSavingFails(): void
    {
        $request = new UploadImageRequest(
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'photo.jpg',
            mimeType: 'image/jpeg',
            fileSize: 999,
            localFilePath: '/tmp/photo.jpg',
        );

        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 1_000, storageQuotaBytes: 10_000),
        );

        $this->mediaFileRepository->expects(self::once())->method('save')->willThrowException(new \RuntimeException('db down'));

        $this->fileStorage->expects(self::once())
            ->method('delete')
            ->with('company/company-id/media/generated-id');

        $this->expectException(\RuntimeException::class);

        $this->uploadImage->execute($request);
    }

    public function testExecuteThrowsWhenCompanyHasNoSubscription(): void
    {
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->with('company-id')->willReturn(null);

        $this->fileStorage->expects(self::never())->method('upload');
        $this->mediaFileRepository->expects(self::never())->method('save');

        $this->expectException(SubscriptionNotFoundException::class);

        $this->uploadImage->execute(new UploadImageRequest(
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'photo.jpg',
            mimeType: 'image/jpeg',
            fileSize: 1234,
            localFilePath: '/tmp/photo.jpg',
        ));
    }

    public function testExecuteThrowsWhenTheFileWouldExceedTheStorageQuota(): void
    {
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->with('company-id')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 9_500, storageQuotaBytes: 10_000),
        );

        $this->fileStorage->expects(self::never())->method('upload');
        $this->mediaFileRepository->expects(self::never())->method('save');

        $this->expectException(StorageQuotaExceededException::class);

        $this->uploadImage->execute(new UploadImageRequest(
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'photo.jpg',
            mimeType: 'image/jpeg',
            fileSize: 1_000,
            localFilePath: '/tmp/photo.jpg',
        ));
    }

    public function testExecuteThrowsWhenTheImageExceedsTheImageMaxSize(): void
    {
        $this->subscriptionRepository->expects(self::never())->method('findByCompanyId');
        $this->fileStorage->expects(self::never())->method('upload');

        $this->expectException(FileTooLargeException::class);

        $this->uploadImage->execute(new UploadImageRequest(
            companyId: 'company-id',
            userId: 'user-id',
            originalFilename: 'photo.jpg',
            mimeType: 'image/jpeg',
            fileSize: 10 * 1024 * 1024 + 1,
            localFilePath: '/tmp/photo.jpg',
        ));
    }
}
