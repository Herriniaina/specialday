<?php

namespace App\Infrastructure\Media\Http;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ConfirmVideoUploadInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'media.filename.not_blank')]
        public string $filename = '',
    ) {
    }
}
