<?php

namespace App\Infrastructure\Security\Keycloak;

final class KeycloakRoles
{
    /**
     * Application roles of a Keycloak token: its ROLE_* realm roles, plus ROLE_USER. Keycloak also
     * grants technical roles (offline_access, default-roles-<realm>…), which are left out.
     *
     * @param array<mixed> $claims
     *
     * @return string[]
     */
    public static function fromClaims(array $claims): array
    {
        $realmAccess = $claims['realm_access'] ?? null;
        $realmRoles = is_array($realmAccess) ? $realmAccess['roles'] ?? [] : [];
        $roles = array_filter(
            is_array($realmRoles) ? $realmRoles : [],
            static fn (mixed $role): bool => is_string($role) && str_starts_with($role, 'ROLE_'),
        );

        return array_values(array_unique(['ROLE_USER', ...$roles]));
    }
}
