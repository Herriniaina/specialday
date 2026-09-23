<?php

namespace App\Application\Prestation;

readonly class CreatePrestationRequest
{
    public function __construct(
        public string $companyId,
        public string $prestationCategoryId,
        public int $amount,
        public string $currency,
        public bool $taxIncluded,
        public int $quantity,
        public string $unit,
        public ?string $notes = null,
    ) {
    }
}
