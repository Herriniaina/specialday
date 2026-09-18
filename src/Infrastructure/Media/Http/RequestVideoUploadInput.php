<?php

namespace App\Infrastructure\Media\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RequestVideoUploadInput
{
    /** @var string[] */
    public const ALLOWED_MIME_TYPES = [
        'video/mp4',
    ];

    public function __construct(
        #[SerializedName('mime_type')]
        #[Assert\NotBlank(message: 'media.mime_type.not_blank')]
        #[Assert\Choice(choices: self::ALLOWED_MIME_TYPES, message: 'media.file.invalid_type')]
        public string $mimeType = '',

        #[SerializedName('file_size')]
        #[Assert\Positive(message: 'media.file_size.positive')]
        public int $fileSize = 0,
    ) {
    }
}
