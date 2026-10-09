<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\GetMediaFile;
use App\Application\Media\GetMediaFileRequest;
use App\Application\Media\ImageUrlSignerInterface;
use App\Application\Media\MediaFileNotFoundException;
use App\Application\Media\PublicVideoUrlBuilderInterface;
use App\Domain\Company\Company;
use App\Domain\Media\MediaStatusEnum;
use App\Domain\Media\MediaTypeEnum;
use App\Infrastructure\Company\Http\CurrentCompany;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetMediaFileController extends ApiAbstractController
{
    public function __construct(
        private GetMediaFile $getMediaFile,
        private ImageUrlSignerInterface $imageUrlSigner,
        private PublicVideoUrlBuilderInterface $publicVideoUrlBuilder,
    ) {
    }

    #[Route('/api/public/media/{mediaId}', name: 'app_public_media_get', methods: ['GET'])]
    public function __invoke(string $mediaId, #[CurrentCompany] Company $company): Response
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

        if (MediaStatusEnum::READY !== $mediaFile->status) {
            return $this->conflictWithPayload(['status' => $mediaFile->status->value]);
        }

        $url = match ($mediaFile->mediaType) {
            MediaTypeEnum::IMAGE => $this->imageUrlSigner->sign($mediaFile->path),
            MediaTypeEnum::VIDEO => $this->publicVideoUrlBuilder->build($mediaFile->path),
        };

        return $this->redirectTo($url);
    }
}
