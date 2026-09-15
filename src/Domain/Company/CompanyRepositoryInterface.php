<?php

namespace App\Domain\Company;

interface CompanyRepositoryInterface
{
    public function save(Company $company): void;

    public function existsBySlug(string $slug): bool;
}
