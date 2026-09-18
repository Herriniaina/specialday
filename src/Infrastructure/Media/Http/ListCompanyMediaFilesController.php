<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\ListCompanyMediaFiles;
use App\Application\Media\ListCompanyMediaFilesRequest;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Media\MediaFile;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListCompanyMediaFilesController extends ApiAbstractController
{
    public function __construct(
        private ListCompanyMediaFiles $listCompanyMediaFiles,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/public/company/{companyId}/media', name: 'app_public_company_media_list', methods: ['GET'])]
    public function __invoke(string $companyId): JsonResponse
    {
        try {
            $mediaFiles = $this->listCompanyMediaFiles->execute(new ListCompanyMediaFilesRequest($companyId));
        } catch (CompanyNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->success(array_map(
            fn (MediaFile $mediaFile): array => [
                'id' => $mediaFile->getId(),
                'company_id' => $mediaFile->companyId,
                'filename' => $mediaFile->filename,
                'media_type' => $mediaFile->mediaType->value,
                // The S3 path stays internal: only GetMediaFileController resolves it to actual bytes.
                'url' => $this->urlGenerator->generate('app_public_media_get', ['mediaId' => $mediaFile->getId()]),
            ],
            $mediaFiles,
        ));
    }
}
