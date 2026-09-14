<?php

namespace App\Application\Shared;

interface SlugGeneratorInterface
{
    public function slugify(string $text): string;
}
