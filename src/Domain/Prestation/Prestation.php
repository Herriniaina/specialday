<?php

namespace App\Domain\Prestation;

use App\Domain\Shared\Price;

class Prestation
{
    public function __construct(
        public string $companyId,
        public string $prestationCategoryId,
        public Price $price,
        public int $quantity,
        public string $unit,
        public \DateTimeImmutable $createdAt,
        public ?string $id = null,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
