<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\StorageQuotaExceededException;
use App\Application\Media\SubscriptionNotFoundException;
use App\Application\Media\UploadImage;
use App\Application\Media\UploadImageRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\User\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UploadImageController extends ApiAbstractController
{
    public function __construct(
        private UploadImage $uploadImage,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * A plain #[MapRequestPayload] can't be used here: Symfony's RequestPayloadValueResolver
     * falls back to treating the raw request body as the payload when a multipart request has
     * no non-file fields (`$request->request->all() ?: $request->getContent()` — an empty files-
     * only form array is falsy), which throws "invalid form data" before the controller runs.
     * Resolving and validating the Input manually sidesteps that resolver bug while keeping the
     * same Input DTO / validation-error envelope as everywhere else.
     */
    #[Route('/api/media/images', name: 'app_media_image_upload', methods: ['POST'])]
    public function __invoke(Request $request, #[CurrentUser] UserEntity $user): JsonResponse
    {
        $company = $user->getCompany();
        if (null === $company) {
            return $this->forbidden('media.company.required');
        }

        $uploadedFile = $request->files->get('file');
        $input = new UploadImageInput($uploadedFile instanceof UploadedFile ? $uploadedFile : null);
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw HttpException::fromStatusCode(422, 'Validation failed.', new ValidationFailedException($input, $violations));
        }

        $file = $input->file;
        \assert(null !== $file);

        try {
            $mediaFile = $this->uploadImage->execute(new UploadImageRequest(
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
            'media_type' => $mediaFile->mediaType->value,
            'storage' => $mediaFile->storage->value,
            'status' => $mediaFile->status->value,
        ]);
    }
}
