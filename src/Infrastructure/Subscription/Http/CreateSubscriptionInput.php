<?php

namespace App\Infrastructure\Subscription\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateSubscriptionInput
{
    public function __construct(
        #[Assert\Choice(choices: ['free', 'standard', 'pro', 'premium'], message: 'subscription.plan.invalid')]
        public string $plan = 'free',

        #[SerializedName('payment_method')]
        #[Assert\Choice(choices: ['stripe', 'mobile_money'], message: 'subscription.payment_method.invalid')]
        public ?string $paymentMethod = null,
    ) {
    }
}
