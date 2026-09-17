<?php

namespace App\Tests\Application\Media;

use App\Application\Media\GetMediaFile;
use App\Application\Media\GetMediaFileRequest;
use App\Application\Media\MediaFileNotFoundException;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\StorageProviderEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetMediaFileTest extends TestCase
{
    private MediaFileRepositoryInterface&MockObject $mediaFileRepository;
    private GetMediaFile $getMediaFile;

    protected function setUp(): void
    {
        $this->mediaFileRepository = $this->createMock(MediaFileRepositoryInterface::class);
        $this->getMediaFile = new GetMediaFile($this->mediaFileRepository);
    }

    public function testExecuteThrowsWhenMediaFileDoesNotExist(): void
    {
        $this->mediaFileRepository->expects(self::once())
            ->method('findById')
            ->with('media-id')
            ->willReturn(null);

        $this->expectException(MediaFileNotFoundException::class);

        $this->getMediaFile->execute(new GetMediaFileRequest('media-id'));
    }

    public function testExecuteReturnsTheMatchedMediaFile(): void
    {
        $mediaFile = new MediaFile(
            companyId: 'company-id',
            filename: 'photo.jpg',
            fileSize: 1234,
            fileType: 'image/jpeg',
            storage: StorageProviderEnum::S3,
            path: 'company/company-id/media/media-id.jpg',
            createdBy: 'user-id',
            createdAt: new \DateTimeImmutable(),
            id: 'media-id',
        );

        $this->mediaFileRepository->expects(self::once())
            ->method('findById')
            ->with('media-id')
            ->willReturn($mediaFile);

        $result = $this->getMediaFile->execute(new GetMediaFileRequest('media-id'));

        self::assertSame($mediaFile, $result);
    }
}
