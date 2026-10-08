<?php

namespace App\Tests\Infrastructure\Security\Keycloak;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Security\Keycloak\ApiUser;
use App\Infrastructure\Security\Keycloak\ApiUserProvider;
use App\Infrastructure\Security\Keycloak\KeycloakUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class ApiUserProviderTest extends TestCase
{
    private function provider(?User $user): ApiUserProvider
    {
        $userRepository = $this->createStub(UserRepositoryInterface::class);
        $userRepository->method('findByIdentityProviderId')->willReturn($user);

        return new ApiUserProvider($userRepository);
    }

    private static function profile(?string $id = 'user-id', ?string $companyId = 'company-id'): User
    {
        return new User('keycloak-id', $companyId, $id);
    }

    public function testBuildsTheUserFromItsProfileAndTheTokenRoles(): void
    {
        $user = $this->provider(self::profile())->loadUserByIdentifier('keycloak-id', [
            'sub' => 'keycloak-id',
            'email' => 'jane@example.com',
            'given_name' => 'Jane',
            'family_name' => 'Doe',
            'phone_number' => '+261340000000',
            'realm_access' => ['roles' => ['ROLE_COMPANY_ADMIN', 'offline_access', 'default-roles-specialday']],
        ]);

        self::assertInstanceOf(ApiUser::class, $user);
        self::assertSame('keycloak-id', $user->getUserIdentifier());
        self::assertSame('user-id', $user->userId);
        self::assertSame('company-id', $user->companyId);
        self::assertSame(['ROLE_USER', 'ROLE_COMPANY_ADMIN'], $user->getRoles());
        self::assertSame('jane@example.com', $user->email);
        self::assertSame('Jane', $user->firstName);
        self::assertSame('Doe', $user->lastName);
        self::assertSame('+261340000000', $user->phoneNumber);
    }

    public function testAUserWithoutCompanyHasANullCompanyId(): void
    {
        $user = $this->provider(self::profile(companyId: null))->loadUserByIdentifier('keycloak-id');

        self::assertInstanceOf(ApiUser::class, $user);
        self::assertNull($user->companyId);
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNull($user->email);
        self::assertNull($user->phoneNumber);
    }

    public function testRejectsAKeycloakAccountWithoutLocalProfile(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->provider(null)->loadUserByIdentifier('keycloak-id');
    }

    public function testRejectsAProfileWithoutId(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->provider(self::profile(id: null))->loadUserByIdentifier('keycloak-id');
    }

    public function testRefreshReturnsTheSameApiUser(): void
    {
        $user = new ApiUser('keycloak-id', 'user-id', null, ['ROLE_USER']);

        self::assertSame($user, $this->provider(null)->refreshUser($user));
    }

    public function testRefreshRejectsOtherUsers(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $this->provider(null)->refreshUser(KeycloakUser::fromClaims(['email' => 'admin@specialday.local']));
    }

    public function testSupportsOnlyApiUsers(): void
    {
        self::assertTrue($this->provider(null)->supportsClass(ApiUser::class));
        self::assertFalse($this->provider(null)->supportsClass(KeycloakUser::class));
    }
}
