<?php

namespace App\Application\Company;

readonly class GetCompanyBySlugRequest
{
    public function __construct(
        public string $slug,
    ) {
    }
}
