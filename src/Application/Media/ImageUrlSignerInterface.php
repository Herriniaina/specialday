<?php

namespace App\Application\Media;

interface ImageUrlSignerInterface
{
    public function sign(string $path, string $operations = ''): string;
}
