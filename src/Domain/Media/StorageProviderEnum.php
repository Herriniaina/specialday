<?php

namespace App\Domain\Media;

enum StorageProviderEnum: string
{
    case S3 = 's3';
    case LOCAL = 'local';
}
