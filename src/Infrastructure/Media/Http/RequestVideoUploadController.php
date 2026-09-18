<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\FileTooLargeException;
use App\Application\Media\RequestVideoUpload;
use App\Application\Media\RequestVideoUploadRequest;
use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\User\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RequestVideoUploadController extends ApiAbstractController
{
    public function __construct(
        private RequestVideoUpload $requestVideoUpload,
    ) {
    }

    #[Route('/api/media/videos', name: 'app_media_video_request_upload', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RequestVideoUploadInput $input,
        #[CurrentUser] UserEntity $user,
    ): JsonResponse {
        $company = $user->getCompany();
        if (null === $company) {
            return $this->forbidden('media.company.required');
        }

        try {
            $result = $this->requestVideoUpload->execute(new RequestVideoUploadRequest(
                companyId: (string) $company->getId(),
                mimeType: $input->mimeType,
                fileSize: $input->fileSize,
            ));
        } catch (SubscriptionNotFoundException $e) {
            return $this->forbidden($e->getMessage());
        } catch (StorageQuotaExceededException|FileTooLargeException $e) {
            return $this->payloadTooLarge($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created([
            'id' => $result->id,
            'upload_url' => $result->uploadUrl,
        ]);
    }
}
