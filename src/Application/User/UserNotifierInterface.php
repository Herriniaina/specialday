<?php

namespace App\Application\User;

use App\Domain\User\User;

interface UserNotifierInterface
{
    public function emailVerificationRequested(User $user, string $verificationToken): void;
}
