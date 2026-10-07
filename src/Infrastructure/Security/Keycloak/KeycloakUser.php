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

        $realmAccess = $claims['realm_access'] ?? null;
        $realmRoles = is_array($realmAccess) ? $realmAccess['roles'] ?? [] : [];
        // Keycloak also grants technical roles (offline_access, default-roles-<realm>…): only the
        // application's ROLE_* roles are kept.
        $roles = array_filter(
            is_array($realmRoles) ? $realmRoles : [],
            static fn (mixed $role): bool => is_string($role) && str_starts_with($role, 'ROLE_'),
        );

        return new self($identifier, array_values(array_unique(['ROLE_USER', ...$roles])));
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
