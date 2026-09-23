<?php

namespace App\Application\Company;

use App\Domain\Shared\Filter;

readonly class ListCompaniesRequest
{
    public function __construct(
        public ?Filter $nameFilter = null,
        public ?Filter $prestationFilter = null,
        public ?Filter $notesFilter = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
