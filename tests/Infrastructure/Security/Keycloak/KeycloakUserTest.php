<?php

namespace App\Tests\Infrastructure\Security\Keycloak;

use App\Infrastructure\Security\Keycloak\KeycloakRequestFailedException;
use App\Infrastructure\Security\Keycloak\KeycloakUser;
use App\Infrastructure\Security\Keycloak\KeycloakUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;

class KeycloakUserTest extends TestCase
{
    public function testKeepsOnlyTheApplicationRealmRoles(): void
    {
        $user = KeycloakUser::fromClaims([
            'email' => 'admin@specialday.local',
            'preferred_username' => 'admin',
            'realm_access' => ['roles' => ['default-roles-specialday', 'offline_access', 'ROLE_ADMIN', 'ROLE_ADMIN', 42]],
        ]);

        self::assertSame('admin@specialday.local', $user->getUserIdentifier());
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
    }

    public function testFallsBackToThePreferredUsernameWithoutRealmRoles(): void
    {
        $user = KeycloakUser::fromClaims(['preferred_username' => 'admin']);

        self::assertSame('admin', $user->getUserIdentifier());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testRejectsClaimsWithoutIdentifier(): void
    {
        $this->expectException(KeycloakRequestFailedException::class);

        KeycloakUser::fromClaims(['email' => '', 'realm_access' => ['roles' => ['ROLE_ADMIN']]]);
    }

    public function testProviderKeepsTheSessionUser(): void
    {
        $provider = new KeycloakUserProvider();
        $user = KeycloakUser::fromClaims(['email' => 'admin@specialday.local']);

        self::assertSame($user, $provider->refreshUser($user));
        self::assertTrue($provider->supportsClass(KeycloakUser::class));
        self::assertFalse($provider->supportsClass(InMemoryUser::class));
    }

    public function testProviderRefusesOtherUsers(): void
    {
        $this->expectException(UnsupportedUserException::class);

        new KeycloakUserProvider()->refreshUser(new InMemoryUser('admin', null));
    }

    public function testProviderCannotLoadUsersByIdentifier(): void
    {
        $this->expectException(UserNotFoundException::class);

        new KeycloakUserProvider()->loadUserByIdentifier('admin@specialday.local');
    }
}
