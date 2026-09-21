<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\GetMediaFile;
use App\Application\Media\GetMediaFileRequest;
use App\Application\Media\MediaFileNotFoundException;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\User\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class GetMediaController extends ApiAbstractController
{
    public function __construct(
        private GetMediaFile $getMediaFile,
    ) {
    }

    #[Route('/api/media/{mediaId}', name: 'app_media_get', methods: ['GET'])]
    public function __invoke(string $mediaId, #[CurrentUser] UserEntity $user): JsonResponse
    {
        $company = $user->getCompany();
        if (null === $company) {
            return $this->forbidden('media.company.required');
        }

        try {
            $mediaFile = $this->getMediaFile->execute(new GetMediaFileRequest($mediaId));
        } catch (MediaFileNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        if ($mediaFile->companyId !== (string) $company->getId()) {
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
