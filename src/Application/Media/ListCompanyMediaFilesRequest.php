<?php

namespace App\Application\Media;

readonly class ListCompanyMediaFilesRequest
{
    public function __construct(
        public string $companyId,
    ) {
    }
}
