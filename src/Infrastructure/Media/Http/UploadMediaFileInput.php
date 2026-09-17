<?php

namespace App\Infrastructure\Media\Http;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UploadMediaFileInput
{
    /** @var string[] */
    public const ALLOWED_MIME_TYPES = [
        // Images: the most optimized/web-friendly formats first.
        'image/webp',
        'image/avif',
        'image/jpeg',
        'image/png',
        // Video
        'video/mp4',
        // Audio
        'audio/mpeg',
        'audio/aac',
        'audio/mp4',
    ];

    public function __construct(
        #[Assert\NotNull(message: 'media.file.not_blank')]
        #[Assert\File(
            maxSize: '100M',
            mimeTypes: self::ALLOWED_MIME_TYPES,
            mimeTypesMessage: 'media.file.invalid_type',
        )]
        public ?UploadedFile $file = null,
    ) {
    }
}
