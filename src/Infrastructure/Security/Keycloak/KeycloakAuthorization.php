<?php

namespace App\Infrastructure\Security\Keycloak;

/**
 * A pending authorization request: the URL to send the browser to, plus the values that must be
 * kept (in session) to validate the callback.
 */
final readonly class KeycloakAuthorization
{
    public function __construct(
        public string $url,
        public string $state,
        public string $codeVerifier,
    ) {
    }
}
