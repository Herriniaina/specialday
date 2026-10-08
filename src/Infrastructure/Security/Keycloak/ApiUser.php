<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * API caller authenticated by a Keycloak access token: the local user it is linked to (by the
 * "sub" claim), with the profile and roles carried by the token.
 */
final readonly class ApiUser implements UserInterface
{
    /**
     * @param non-empty-string $identityProviderId
     * @param string[]         $roles
     */
    public function __construct(
        public string $identityProviderId,
        public string $userId,
        public ?string $companyId,
        private array $roles,
        public ?string $email = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phoneNumber = null,
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->identityProviderId;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
