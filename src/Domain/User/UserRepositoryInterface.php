<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function existsByEmail(string $email): bool;

    public function findById(string $id): ?User;

    public function findByEmailVerificationToken(string $token): ?User;

    public function activate(string $userId): void;
}
