<?php

namespace App\Application\Company;

readonly class CreateCompanyRequest
{
    public function __construct(
        public string $name,
        public ?string $nif = null,
        public ?string $stat = null,
        public ?string $rcs = null,
        public ?string $phone = null,
        public ?string $instagramProfile = null,
        public ?string $facebookProfile = null,
        public ?string $youtubeProfile = null,
    ) {
    }
}
