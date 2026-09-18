<?php

namespace App\Infrastructure\Media\MessageHandler;

use App\Application\Media\TranscodeVideo as TranscodeVideoUseCase;
use App\Application\Media\TranscodeVideoRequest;
use App\Infrastructure\Media\Message\TranscodeVideo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TranscodeVideoHandler
{
    public function __construct(
        private TranscodeVideoUseCase $transcodeVideo,
    ) {
    }

    public function __invoke(TranscodeVideo $message): void
    {
        $this->transcodeVideo->execute(new TranscodeVideoRequest($message->mediaFileId));
    }
}
