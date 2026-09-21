<?php

namespace App\Infrastructure\Media\Http;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UploadImageInput
{
    /** @var string[] */
    public const ALLOWED_MIME_TYPES = [
        // Most optimized/web-friendly formats first.
        'image/webp',
        'image/avif',
        'image/jpeg',
        'image/png',
    ];

    public function __construct(
        #[Assert\NotNull(message: 'media.file.not_blank')]
        #[Assert\File(
            maxSize: '10M',
            mimeTypes: self::ALLOWED_MIME_TYPES,
            mimeTypesMessage: 'media.file.invalid_type',
        )]
        public ?UploadedFile $file = null,
    ) {
    }
}
