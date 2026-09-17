<?php

namespace App\Application\User;

readonly class ChangePasswordRequest
{
    public function __construct(
        public string $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {
    }
}
