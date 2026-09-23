<?php

namespace App\Domain\Company;

readonly class CompanySearchResult
{
    /**
     * @param Company[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }
}
