<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Application\Media\UploadMediaFile;
use App\Application\Media\UploadMediaFileRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\User\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UploadMediaFileController extends ApiAbstractController
{
    public function __construct(
        private UploadMediaFile $uploadMediaFile,
    ) {
    }

    #[Route('/api/media', name: 'app_media_upload', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] UploadMediaFileInput $input,
        #[CurrentUser] UserEntity $user,
    ): JsonResponse {
        $company = $user->getCompany();
        if (null === $company) {
            return $this->forbidden('media.company.required');
        }

        $file = $input->file;
        \assert(null !== $file);

        try {
            $mediaFile = $this->uploadMediaFile->execute(new UploadMediaFileRequest(
                companyId: (string) $company->getId(),
                userId: (string) $user->getId(),
                originalFilename: (string) $file->getClientOriginalName(),
                mimeType: (string) $file->getMimeType(),
                fileSize: (int) $file->getSize(),
                localFilePath: $file->getPathname(),
            ));
        } catch (SubscriptionNotFoundException $e) {
            return $this->forbidden($e->getMessage());
        } catch (StorageQuotaExceededException $e) {
            return $this->payloadTooLarge($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created([
            'id' => $mediaFile->getId(),
            'filename' => $mediaFile->filename,
            'file_size' => $mediaFile->fileSize,
            'file_type' => $mediaFile->fileType,
            'storage' => $mediaFile->storage->value,
        ]);
    }
}
