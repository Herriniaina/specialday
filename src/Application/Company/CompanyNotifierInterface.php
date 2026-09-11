<?php

namespace App\Application\Company;

use App\Domain\Company\Company;

interface CompanyNotifierInterface
{
    public function companyRegistered(Company $company): void;
}
