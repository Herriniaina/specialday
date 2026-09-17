<?php

namespace App\Tests\Application\Media;

use App\Application\Media\ListCompanyMediaFiles;
use App\Application\Media\ListCompanyMediaFilesRequest;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\StorageProviderEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListCompanyMediaFilesTest extends TestCase
{
    private MediaFileRepositoryInterface&MockObject $mediaFileRepository;
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private ListCompanyMediaFiles $listCompanyMediaFiles;

    protected function setUp(): void
    {
        $this->mediaFileRepository = $this->createMock(MediaFileRepositoryInterface::class);
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->listCompanyMediaFiles = new ListCompanyMediaFiles($this->mediaFileRepository, $this->companyRepository);
    }

    public function testExecuteThrowsWhenCompanyDoesNotExist(): void
    {
        $this->companyRepository->expects(self::once())->method('existsById')->with('company-id')->willReturn(false);

        $this->mediaFileRepository->expects(self::never())->method('findByCompanyId');

        $this->expectException(CompanyNotFoundException::class);

        $this->listCompanyMediaFiles->execute(new ListCompanyMediaFilesRequest('company-id'));
    }

    public function testExecuteReturnsTheCompanysMediaFiles(): void
    {
        $this->companyRepository->expects(self::once())->method('existsById')->with('company-id')->willReturn(true);

        $mediaFile = new MediaFile(
            companyId: 'company-id',
            filename: 'photo.jpg',
            fileSize: 1234,
            fileType: 'image/jpeg',
            storage: StorageProviderEnum::S3,
            path: 'company/company-id/media/generated-id.jpg',
            createdBy: 'user-id',
            createdAt: new \DateTimeImmutable(),
            id: 'media-id',
        );

        $this->mediaFileRepository->expects(self::once())
            ->method('findByCompanyId')
            ->with('company-id')
            ->willReturn([$mediaFile]);

        $mediaFiles = $this->listCompanyMediaFiles->execute(new ListCompanyMediaFilesRequest('company-id'));

        self::assertSame([$mediaFile], $mediaFiles);
    }
}
