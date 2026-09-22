<?php

namespace App\Application\Subscription;

use App\Domain\Subscription\PaymentMethodEnum;
use App\Domain\Subscription\PlanEnum;

readonly class CreateSubscriptionRequest
{
    public function __construct(
        public string $companyId,
        public PlanEnum $plan,
        public ?PaymentMethodEnum $paymentMethod,
    ) {
    }
}
