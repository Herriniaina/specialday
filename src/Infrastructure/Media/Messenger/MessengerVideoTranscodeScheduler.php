<?php

namespace App\Infrastructure\Media\Messenger;

use App\Application\Media\VideoTranscodeSchedulerInterface;
use App\Infrastructure\Media\Message\TranscodeVideo;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerVideoTranscodeScheduler implements VideoTranscodeSchedulerInterface
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    #[\Override]
    public function schedule(string $mediaFileId): void
    {
        $this->bus->dispatch(new TranscodeVideo($mediaFileId));
    }
}
