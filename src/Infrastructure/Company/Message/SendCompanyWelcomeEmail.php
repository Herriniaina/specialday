<?php

namespace App\Infrastructure\Company\Message;

readonly class SendCompanyWelcomeEmail
{
    public function __construct(
        public string $companyName,
        public string $email,
        public string $token,
        public string $accessCode,
    ) {
    }
}
