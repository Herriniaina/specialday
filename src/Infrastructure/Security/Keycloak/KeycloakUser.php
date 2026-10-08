<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Back-office user authenticated by Keycloak, built from the access token claims. Keycloak is the
 * source of truth for the roles (realm roles), nothing is read from the local "user" table.
 */
final readonly class KeycloakUser implements UserInterface
{
    /**
     * @param non-empty-string $identifier
     * @param string[]         $roles
     */
    private function __construct(
        private string $identifier,
        private array $roles,
    ) {
    }

    /**
     * @param array<mixed> $claims
     */
    public static function fromClaims(array $claims): self
    {
        $identifier = $claims['email'] ?? $claims['preferred_username'] ?? null;
        if (!is_string($identifier) || '' === $identifier) {
            throw new KeycloakRequestFailedException('Keycloak access token has neither an "email" nor a "preferred_username" claim.');
        }

        return new self($identifier, KeycloakRoles::fromClaims($claims));
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
