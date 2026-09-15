<?php

namespace App\Application\User;

readonly class VerifyUserEmailRequest
{
    public function __construct(
        public string $token,
    ) {
    }
}
