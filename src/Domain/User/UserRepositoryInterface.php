<?php

namespace App\Domain\User;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(string $id): ?User;

    public function findByIdentityProviderId(string $identityProviderId): ?User;

    /**
     * Links the user to the company, unless it already belongs to one (checked atomically).
     *
     * @return bool false when the user was already linked to a company (or does not exist)
     */
    public function attachToCompany(string $userId, string $companyId): bool;
}
