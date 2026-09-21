<?php

namespace App\Tests\Application\Media;

use App\Application\Media\FileTooLargeException;
use App\Application\Media\RequestVideoUpload;
use App\Application\Media\RequestVideoUploadRequest;
use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Application\Media\VideoUploadUrlGeneratorInterface;
use App\Application\Shared\IdGeneratorInterface;
use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class RequestVideoUploadTest extends TestCase
{
    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;
    private VideoUploadUrlGeneratorInterface&MockObject $videoUploadUrlGenerator;
    private IdGeneratorInterface&Stub $idGenerator;
    private RequestVideoUpload $requestVideoUpload;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->videoUploadUrlGenerator = $this->createMock(VideoUploadUrlGeneratorInterface::class);
        $this->idGenerator = $this->createStub(IdGeneratorInterface::class);
        $this->idGenerator->method('generate')->willReturn('generated-id');

        $this->requestVideoUpload = new RequestVideoUpload($this->subscriptionRepository, $this->videoUploadUrlGenerator, $this->idGenerator);
    }

    public function testExecuteReturnsAPresignedUploadUrlForTheDeterministicPath(): void
    {
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->with('company-id')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 1_000, storageQuotaBytes: 10_000),
        );

        $this->videoUploadUrlGenerator->expects(self::once())
            ->method('generate')
            ->with('company/company-id/media/generated-id')
            ->willReturn('https://s3.example.com/presigned-put');

        $result = $this->requestVideoUpload->execute(new RequestVideoUploadRequest(
            companyId: 'company-id',
            mimeType: 'video/mp4',
            fileSize: 1234,
        ));

        self::assertSame('generated-id', $result->id);
        self::assertSame('https://s3.example.com/presigned-put', $result->uploadUrl);
    }

    public function testExecuteThrowsWhenAVideoExceedsTheVideoMaxSize(): void
    {
        $this->subscriptionRepository->expects(self::never())->method('findByCompanyId');
        $this->videoUploadUrlGenerator->expects(self::never())->method('generate');

        $this->expectException(FileTooLargeException::class);

        $this->requestVideoUpload->execute(new RequestVideoUploadRequest(
            companyId: 'company-id',
            mimeType: 'video/mp4',
            fileSize: 2 * 1024 * 1024 * 1024 + 1,
        ));
    }

    public function testExecuteThrowsWhenCompanyHasNoSubscription(): void
    {
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(null);
        $this->videoUploadUrlGenerator->expects(self::never())->method('generate');

        $this->expectException(SubscriptionNotFoundException::class);

        $this->requestVideoUpload->execute(new RequestVideoUploadRequest(
            companyId: 'company-id',
            mimeType: 'video/mp4',
            fileSize: 1234,
        ));
    }

    public function testExecuteThrowsWhenTheFileWouldExceedTheStorageQuota(): void
    {
        $this->subscriptionRepository->expects(self::once())->method('findByCompanyId')->willReturn(
            new Subscription(id: 'subscription-id', companyId: 'company-id', storageUsedBytes: 9_500, storageQuotaBytes: 10_000),
        );
        $this->videoUploadUrlGenerator->expects(self::never())->method('generate');

        $this->expectException(StorageQuotaExceededException::class);

        $this->requestVideoUpload->execute(new RequestVideoUploadRequest(
            companyId: 'company-id',
            mimeType: 'video/mp4',
            fileSize: 1_000,
        ));
    }
}
