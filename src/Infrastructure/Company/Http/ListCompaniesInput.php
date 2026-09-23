<?php

namespace App\Infrastructure\Company\Http;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ListCompaniesInput
{
    public function __construct(
        public ?string $name = null,

        public ?string $prestation = null,

        public ?string $notes = null,

        #[Assert\Positive(message: 'company.list.page.positive')]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'company.list.limit.range')]
        public int $limit = 20,
    ) {
    }
}
