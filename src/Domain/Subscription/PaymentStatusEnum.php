<?php

namespace App\Domain\Subscription;

enum PaymentStatusEnum: string
{
    // Stripe: waiting on the first payment (3D Secure).
    case INCOMPLETE = 'incomplete';
    // Mobile Money: user claims payment was made, awaiting admin review.
    case PENDING_REVIEW = 'pending_review';
    case PAID = 'paid';
    // Due date passed (grace period / dunning).
    case PAST_DUE = 'past_due';
    case FAILED = 'failed';
    // Subscription suspended for non-payment.
    case UNPAID = 'unpaid';
    case REFUNDED = 'refunded';
    case VOIDED = 'voided';
    case NOT_REQUIRED = 'not_required';
}
