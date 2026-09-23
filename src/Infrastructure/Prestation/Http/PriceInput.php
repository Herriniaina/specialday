<?php

namespace App\Infrastructure\Prestation\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class PriceInput
{
    public function __construct(
        #[Assert\NotNull(message: 'prestation.price.amount.not_blank')]
        #[Assert\PositiveOrZero(message: 'prestation.price.amount.positive_or_zero')]
        public ?int $amount = null,

        #[Assert\NotBlank(message: 'prestation.price.currency.not_blank')]
        #[Assert\Length(max: 10, maxMessage: 'prestation.price.currency.too_long')]
        public string $currency = '',

        #[SerializedName('tax_included')]
        #[Assert\NotNull(message: 'prestation.price.tax_included.not_blank')]
        public ?bool $taxIncluded = null,
    ) {
    }
}
