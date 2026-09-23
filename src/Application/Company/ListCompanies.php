<?php

namespace App\Application\Company;

use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Company\CompanySearchCriteria;
use App\Domain\Company\CompanySearchResult;

class ListCompanies
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
    ) {
    }

    public function execute(ListCompaniesRequest $request): CompanySearchResult
    {
        return $this->companyRepository->search(new CompanySearchCriteria(
            nameFilter: $request->nameFilter,
            prestationFilter: $request->prestationFilter,
            notesFilter: $request->notesFilter,
            page: max(1, $request->page),
            limit: $request->limit,
        ));
    }
}
