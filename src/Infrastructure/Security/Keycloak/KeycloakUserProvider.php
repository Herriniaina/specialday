<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Keycloak users only exist through the tokens of a login (see KeycloakAuthenticator): there is
 * nothing to load by identifier, and the session copy is kept as is until logout.
 *
 * @implements UserProviderInterface<KeycloakUser>
 */
final class KeycloakUserProvider implements UserProviderInterface
{
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof KeycloakUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return KeycloakUser::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $exception = new UserNotFoundException('Keycloak users can only be loaded from a login.');
        $exception->setUserIdentifier($identifier);

        throw $exception;
    }
}
