<?php

namespace App\Domain\Subscription;

enum PlanEnum: string
{
    case FREE = 'free';
    case STANDARD = 'standard';
    case PRO = 'pro';
    case PREMIUM = 'premium';
}
