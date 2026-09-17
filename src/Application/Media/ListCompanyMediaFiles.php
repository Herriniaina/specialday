<?php

namespace App\Application\Media;

use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;

class ListCompanyMediaFiles
{
    public function __construct(
        private MediaFileRepositoryInterface $mediaFileRepository,
        private CompanyRepositoryInterface $companyRepository,
    ) {
    }

    /**
     * @return MediaFile[]
     */
    public function execute(ListCompanyMediaFilesRequest $request): array
    {
        if (!$this->companyRepository->existsById($request->companyId)) {
            throw new CompanyNotFoundException('Company not found.');
        }

        return $this->mediaFileRepository->findByCompanyId($request->companyId);
    }
}
