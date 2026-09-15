<?php

namespace App\Tests\Infrastructure\Shared\Messenger;

use App\Infrastructure\Shared\Messenger\DeadlineRetryStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

class DeadlineRetryStrategyTest extends TestCase
{
    private function envelopeWithRetryCount(int $retryCount): Envelope
    {
        $stamps = $retryCount > 0 ? [new RedeliveryStamp($retryCount)] : [];

        return new Envelope(new \stdClass(), $stamps);
    }

    public function testIsRetryableIsTrueWhileWithinTheRetryWindow(): void
    {
        $strategy = new DeadlineRetryStrategy(retryWindowSeconds: 3600);

        self::assertTrue($strategy->isRetryable($this->envelopeWithRetryCount(0)));
        self::assertTrue($strategy->isRetryable($this->envelopeWithRetryCount(3)));
    }

    public function testIsRetryableIsFalseOnceTheRetryWindowIsExceeded(): void
    {
        $strategy = new DeadlineRetryStrategy(retryWindowSeconds: 10);

        // scheduledDelayAfter(3) = (2^4)-2 = 14, which is beyond the 10s window.
        self::assertFalse($strategy->isRetryable($this->envelopeWithRetryCount(3)));
    }

    public function testGetWaitingTimeGrowsExponentiallyWithRetryCount(): void
    {
        $strategy = new DeadlineRetryStrategy(retryWindowSeconds: 3600);

        self::assertSame(2000, $strategy->getWaitingTime($this->envelopeWithRetryCount(0)));
        self::assertSame(16000, $strategy->getWaitingTime($this->envelopeWithRetryCount(3)));
    }

    public function testGetWaitingTimeIsClampedToTheRemainingRetryWindow(): void
    {
        $strategy = new DeadlineRetryStrategy(retryWindowSeconds: 10);

        // scheduledDelayAfter(2) = 6, remaining = 10 - 6 = 4, min(2^3, 4) = 4.
        self::assertSame(4000, $strategy->getWaitingTime($this->envelopeWithRetryCount(2)));
    }
}
