<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\FileTooLargeException;
use App\Application\Media\RequestVideoUpload;
use App\Application\Media\RequestVideoUploadRequest;
use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Domain\Company\Company;
use App\Infrastructure\Company\Http\CurrentCompany;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RequestVideoUploadController extends ApiAbstractController
{
    public function __construct(
        private RequestVideoUpload $requestVideoUpload,
    ) {
    }

    #[Route('/api/media/videos', name: 'app_media_video_request_upload', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RequestVideoUploadInput $input,
        #[CurrentCompany] Company $company,
    ): JsonResponse {
        try {
            $result = $this->requestVideoUpload->execute(new RequestVideoUploadRequest(
                companyId: $company->getId(),
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
