<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\ConfirmVideoUpload;
use App\Application\Media\ConfirmVideoUploadRequest;
use App\Application\Media\FileTooLargeException;
use App\Application\Media\MediaFileNotFoundException;
use App\Application\Media\MediaObjectNotFoundException;
use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Application\Media\UnsupportedMediaTypeException;
use App\Domain\Company\Company;
use App\Domain\Media\MediaStatusEnum;
use App\Infrastructure\Company\Http\CurrentCompany;
use App\Infrastructure\Security\Keycloak\ApiUser;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ConfirmVideoUploadController extends ApiAbstractController
{
    public function __construct(
        private ConfirmVideoUpload $confirmVideoUpload,
    ) {
    }

    #[Route('/api/media/videos/{mediaId}/confirm', name: 'app_media_video_confirm_upload', methods: ['POST'])]
    public function __invoke(
        string $mediaId,
        #[MapRequestPayload] ConfirmVideoUploadInput $input,
        #[CurrentCompany] Company $company,
        #[CurrentUser] ApiUser $user,
    ): JsonResponse {
        try {
            $mediaFile = $this->confirmVideoUpload->execute(new ConfirmVideoUploadRequest(
                mediaId: $mediaId,
                companyId: $company->getId(),
                userId: $user->userId,
                originalFilename: $input->filename,
            ));
        } catch (MediaFileNotFoundException|MediaObjectNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (UnsupportedMediaTypeException $e) {
            return $this->unsupportedMediaType($e->getMessage());
        } catch (SubscriptionNotFoundException $e) {
            return $this->forbidden($e->getMessage());
        } catch (StorageQuotaExceededException|FileTooLargeException $e) {
            return $this->payloadTooLarge($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        $payload = [
            'id' => $mediaFile->getId(),
            'filename' => $mediaFile->filename,
            'file_size' => $mediaFile->fileSize,
            'file_type' => $mediaFile->fileType,
            'media_type' => $mediaFile->mediaType->value,
            'storage' => $mediaFile->storage->value,
            'status' => $mediaFile->status->value,
        ];

        return MediaStatusEnum::PROCESSING === $mediaFile->status
            ? $this->accepted($payload)
            : $this->created($payload);
    }
}
