<?php

namespace App\Infrastructure\User\Security;

use App\Application\User\PasswordHasherInterface;
use App\Infrastructure\User\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function hash(string $plainPassword): string
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(User::class)
            ->hash($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(User::class)
            ->verify($hashedPassword, $plainPassword);
    }
}
