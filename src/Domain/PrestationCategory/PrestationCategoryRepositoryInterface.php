<?php

namespace App\Domain\PrestationCategory;

interface PrestationCategoryRepositoryInterface
{
    public function existsById(string $id): bool;
}
