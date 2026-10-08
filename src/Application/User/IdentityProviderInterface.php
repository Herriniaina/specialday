<?php

namespace App\Application\User;

/**
 * The identity provider (Keycloak) owns the accounts: credentials, email verification and roles.
 */
interface IdentityProviderInterface
{
    /**
     * @param string[] $roles
     *
     * @return string the id of the created account
     *
     * @throws EmailAlreadyExistsException when an account already uses this email
     * @throws AccountRejectedException    when the account breaks a rule of the provider (password policy…)
     */
    public function createAccount(string $email, string $password, string $firstName, string $lastName, ?string $phoneNumber, array $roles): string;

    public function sendEmailVerification(string $accountId): void;

    public function deleteAccount(string $accountId): void;
}
