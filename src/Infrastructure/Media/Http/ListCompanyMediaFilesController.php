<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\ListCompanyMediaFiles;
use App\Application\Media\ListCompanyMediaFilesRequest;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Media\MediaFile;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListCompanyMediaFilesController extends ApiAbstractController
{
    public function __construct(
        private ListCompanyMediaFiles $listCompanyMediaFiles,
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
            static fn (MediaFile $mediaFile): array => [
                'id' => $mediaFile->getId(),
                'company_id' => $mediaFile->companyId,
                'filename' => $mediaFile->filename,
                // The S3 path stays internal: the public detail route (/api/public/media/{mediaId}, not built yet)
                // will be the only thing that resolves it to an actual file.
                'url' => \sprintf('/api/public/media/%s', $mediaFile->getId()),
            ],
            $mediaFiles,
        ));
    }
}
