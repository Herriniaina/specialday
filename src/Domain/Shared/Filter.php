<?php

namespace App\Domain\Shared;

readonly class Filter
{
    /**
     * @param string[] $values
     */
    public function __construct(
        public FilterOperator $operator,
        public array $values,
    ) {
    }
}
