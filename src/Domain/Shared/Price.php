<?php

namespace App\Domain\Shared;

readonly class Price
{
    public function __construct(
        public int $amount,
        public string $currency,
        public bool $taxIncluded,
    ) {
    }
}
