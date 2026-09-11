<?php

namespace App\Domain\Company;

interface CompanyRepositoryInterface
{
    public function save(Company $company): void;

    public function existsByEmail(string $email): bool;

    public function findByToken(string $token): ?Company;
}
