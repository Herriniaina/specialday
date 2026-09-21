<?php

namespace App\Application\Media;

use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaStatusEnum;

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

        $mediaFiles = $this->mediaFileRepository->findByCompanyId($request->companyId);

        return array_values(array_filter(
            $mediaFiles,
            static fn (MediaFile $mediaFile): bool => MediaStatusEnum::READY === $mediaFile->status,
        ));
    }
}
