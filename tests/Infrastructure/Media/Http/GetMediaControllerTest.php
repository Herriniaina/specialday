<?php

namespace App\Tests\Infrastructure\Media\Http;

use App\Application\Media\GetMediaFile;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaStatusEnum;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Media\StorageProviderEnum;
use App\Infrastructure\Company\Entity\Company;
use App\Infrastructure\Media\Http\GetMediaController;
use App\Infrastructure\User\Entity\User;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetMediaControllerTest extends TestCase
{
    private MediaFileRepositoryInterface&Stub $mediaFileRepository;
    private GetMediaController $controller;

    protected function setUp(): void
    {
        $this->mediaFileRepository = $this->createStub(MediaFileRepositoryInterface::class);
        $this->controller = new GetMediaController(new GetMediaFile($this->mediaFileRepository));
    }

    private function companyAdmin(string $companyId = 'company-id'): User
    {
        return (new User())->setCompany((new Company())->setId($companyId));
    }

    private function mediaFile(string $companyId): MediaFile
    {
        return new MediaFile(
            companyId: $companyId,
            filename: 'photo.jpg',
            fileSize: 100,
            fileType: 'image/jpeg',
            mediaType: MediaTypeEnum::IMAGE,
            storage: StorageProviderEnum::S3,
            path: 'company/'.$companyId.'/media/media-id',
            createdBy: 'user-id',
            createdAt: new \DateTimeImmutable(),
            id: 'media-id',
            status: MediaStatusEnum::READY,
        );
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
        $response = ($this->controller)('media-id', new User());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('media.company.required', $this->body($response)['statusText']);
    }

    public function testReturnsNotFoundWhenTheMediaFileDoesNotExist(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn(null);

        $response = ($this->controller)('media-id', $this->companyAdmin());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testReturnsNotFoundWhenTheMediaFileBelongsToAnotherCompany(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn($this->mediaFile('other-company-id'));

        $response = ($this->controller)('media-id', $this->companyAdmin());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('Media file not found.', $this->body($response)['statusText']);
    }

    public function testReturnsTheMediaFileForTheOwningCompany(): void
    {
        $this->mediaFileRepository->method('findById')->willReturn($this->mediaFile('company-id'));

        $response = ($this->controller)('media-id', $this->companyAdmin());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('media-id', $this->body($response)['payload']['id']);
        self::assertSame('photo.jpg', $this->body($response)['payload']['filename']);
    }
}
