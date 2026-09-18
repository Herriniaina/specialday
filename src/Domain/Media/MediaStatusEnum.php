<?php

namespace App\Domain\Media;

enum MediaStatusEnum: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';
}
