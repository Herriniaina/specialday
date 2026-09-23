<?php

namespace App\Domain\Company;

interface CompanyRepositoryInterface
{
    public function save(Company $company): void;

    public function existsBySlug(string $slug): bool;

    public function existsById(string $id): bool;

    public function search(CompanySearchCriteria $criteria): CompanySearchResult;
}
