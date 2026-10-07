<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Ends the Keycloak SSO session too: otherwise the next visit to the back-office would log the
 * user straight back in without asking for credentials.
 */
final readonly class KeycloakLogoutListener
{
    public function __construct(
        private KeycloakClient $keycloakClient,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (null === $token || !$token->hasAttribute(KeycloakAuthenticator::ID_TOKEN_ATTRIBUTE)) {
            return;
        }

        $idToken = $token->getAttribute(KeycloakAuthenticator::ID_TOKEN_ATTRIBUTE);
        if (!is_string($idToken)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->keycloakClient->logoutUrl(
            $idToken,
            $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
        )));
    }
}
