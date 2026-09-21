<?php

namespace App\Application\Media;

interface PublicVideoUrlBuilderInterface
{
    public function build(string $path): string;
}
