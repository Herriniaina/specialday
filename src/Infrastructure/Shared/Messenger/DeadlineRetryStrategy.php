<?php

namespace App\Infrastructure\Shared\Messenger;

use Override;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;


final class DeadlineRetryStrategy implements RetryStrategyInterface
{
    public function __construct(private int $retryWindowSeconds = 3600)
    {
    }

    #[Override]
    public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
    {
        return $this->scheduledDelayAfter($this->retryCount($message)) < $this->retryWindowSeconds;
    }

    #[Override]
    public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
    {
        $attempt = $this->retryCount($message) + 1;
        $remainingSeconds = $this->retryWindowSeconds - $this->scheduledDelayAfter($attempt - 1);
        $waitSeconds = min(2 ** $attempt, max(1, $remainingSeconds));

        return $waitSeconds * 1000;
    }

    private function retryCount(Envelope $message): int
    {
        return $message->last(RedeliveryStamp::class)?->getRetryCount() ?? 0;
    }

    private function scheduledDelayAfter(int $completedRetries): int
    {
        return $completedRetries <= 0 ? 0 : (2 ** ($completedRetries + 1)) - 2;
    }
}
