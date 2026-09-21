<?php

namespace App\Application\Media;

readonly class RequestVideoUploadResult
{
    public function __construct(
        public string $id,
        public string $uploadUrl,
    ) {
    }
}
