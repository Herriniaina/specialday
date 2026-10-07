<?php

namespace App\Infrastructure\Security\Keycloak;

final readonly class KeycloakTokens
{
    /**
     * @param array<mixed> $accessTokenClaims
     */
    public function __construct(
        public array $accessTokenClaims,
        public string $idToken,
    ) {
    }
}
