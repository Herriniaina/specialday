<?php

namespace App\Application\User;

readonly class RegisterUserRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public array $roles,
        public string $firstName,
        public string $lastName,
        public string $phoneNumber,
    ) {
    }
}
