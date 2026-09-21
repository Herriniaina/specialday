<?php

namespace App\Infrastructure\Subscription\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateSubscriptionInput
{
    public function __construct(
        #[SerializedName('company_id')]
        #[Assert\NotBlank(message: 'company.id.not_blank')]
        #[Assert\Uuid(message: 'company.id.invalid')]
        public string $companyId = '',

        #[Assert\Choice(choices: ['free', 'standard', 'pro', 'premium'], message: 'subscription.plan.invalid')]
        public string $plan = 'free',

        #[SerializedName('payment_method')]
        #[Assert\Choice(choices: ['stripe', 'mobile_money'], message: 'subscription.payment_method.invalid')]
        public ?string $paymentMethod = null,
    ) {
    }
}
