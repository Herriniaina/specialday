<?php

namespace App\Application\User;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;
}
