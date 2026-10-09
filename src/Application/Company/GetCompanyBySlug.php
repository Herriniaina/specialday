<?php

namespace App\Application\Company;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;

class GetCompanyBySlug
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
    ) {
    }

    public function execute(GetCompanyBySlugRequest $request): Company
    {
        $company = $this->companyRepository->findBySlug($request->slug);
        if (null === $company) {
            throw new CompanyNotFoundException('Company not found.');
        }

        return $company;
    }
}
