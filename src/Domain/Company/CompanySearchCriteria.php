<?php

namespace App\Domain\Company;

use App\Domain\Shared\Filter;

readonly class CompanySearchCriteria
{
    public function __construct(
        public ?Filter $nameFilter,
        public ?Filter $prestationFilter,
        public ?Filter $notesFilter,
        public int $page,
        public int $limit,
    ) {
    }
}
