<?php

namespace App\Domain\Media;

enum MediaTypeEnum: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';

    public static function fromMimeType(string $mimeType): self
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => self::IMAGE,
            str_starts_with($mimeType, 'video/') => self::VIDEO,
            default => throw new \InvalidArgumentException(\sprintf('Unsupported mime type "%s".', $mimeType)),
        };
    }

    public function maxFileSizeInBytes(): int
    {
        return match ($this) {
            self::IMAGE => 10 * 1024 * 1024,
            self::VIDEO => 2 * 1024 * 1024 * 1024,
        };
    }
}
