<?php

namespace App\Application\Shared;

interface IdGeneratorInterface
{
    public function generate(): string;
}
