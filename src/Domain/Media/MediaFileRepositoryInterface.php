<?php

namespace App\Domain\Media;

interface MediaFileRepositoryInterface
{
    public function save(MediaFile $mediaFile): void;
}
