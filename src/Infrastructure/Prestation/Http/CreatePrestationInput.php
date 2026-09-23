<?php

namespace App\Infrastructure\Prestation\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreatePrestationInput
{
    public function __construct(
        #[SerializedName('prestation_category_id')]
        #[Assert\NotBlank(message: 'prestation.prestation_category_id.not_blank')]
        #[Assert\Uuid(message: 'prestation.prestation_category_id.invalid')]
        public string $prestationCategoryId = '',

        #[Assert\Valid]
        public PriceInput $price = new PriceInput(),

        #[Assert\NotNull(message: 'prestation.quantity.not_blank')]
        #[Assert\Positive(message: 'prestation.quantity.positive')]
        public ?int $quantity = null,

        #[Assert\NotBlank(message: 'prestation.unit.not_blank')]
        #[Assert\Length(max: 50, maxMessage: 'prestation.unit.too_long')]
        public string $unit = '',

        public ?string $notes = null,
    ) {
    }
}
