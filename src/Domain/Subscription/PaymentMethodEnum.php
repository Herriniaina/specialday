<?php

namespace App\Domain\Subscription;

enum PaymentMethodEnum: string
{
    case STRIPE = 'stripe';
    case MOBILE_MONEY = 'mobile_money';
}
