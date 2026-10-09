<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\GetMediaFile;
use App\Application\Media\GetMediaFileRequest;
use App\Application\Media\MediaFileNotFoundException;
use App\Domain\Company\Company;
use App\Infrastructure\Company\Http\CurrentCompany;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetMediaController extends ApiAbstractController
{
    public function __construct(
        private GetMediaFile $getMediaFile,
    ) {
    }

    #[Route('/api/media/{mediaId}', name: 'app_media_get', methods: ['GET'])]
    public function __invoke(string $mediaId, #[CurrentCompany] Company $company): JsonResponse
    {
        try {
            $mediaFile = $this->getMediaFile->execute(new GetMediaFileRequest($mediaId));
        } catch (MediaFileNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        if ($mediaFile->companyId !== $company->getId()) {
            return $this->notFound('Media file not found.');
        }

        return $this->success([
            'id' => $mediaFile->getId(),
            'filename' => $mediaFile->filename,
            'file_size' => $mediaFile->fileSize,
            'file_type' => $mediaFile->fileType,
            'media_type' => $mediaFile->mediaType->value,
            'storage' => $mediaFile->storage->value,
            'status' => $mediaFile->status->value,
            'duration_seconds' => $mediaFile->durationSeconds,
        ]);
    }
}
