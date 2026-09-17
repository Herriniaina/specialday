<?php

namespace App\Application\Media;

readonly class GetMediaFileRequest
{
    public function __construct(
        public string $mediaId,
    ) {
    }
}
