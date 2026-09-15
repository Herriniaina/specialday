<?php

namespace App\Infrastructure\User\Message;

readonly class SendUserEmailVerification
{
    public function __construct(
        public string $email,
        public ?string $firstName,
        public string $token,
    ) {
    }
}
