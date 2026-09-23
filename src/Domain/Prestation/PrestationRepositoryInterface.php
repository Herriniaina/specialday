<?php

namespace App\Domain\Prestation;

interface PrestationRepositoryInterface
{
    public function save(Prestation $prestation): void;
}
