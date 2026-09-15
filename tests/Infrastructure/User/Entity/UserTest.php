<?php

namespace App\Tests\Infrastructure\User\Entity;

use App\Infrastructure\User\Entity\User;
use App\Infrastructure\User\UserEmailMissingException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class UserTest extends TestCase
{
    private function markAsPersisted(User $user): User
    {
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setValue($user, Uuid::uuid7());

        return $user;
    }

    public function testGetUserIdentifierThrowsWhenEmailIsMissing(): void
    {
        $user = new User();

        $this->expectException(UserEmailMissingException::class);

        $user->getUserIdentifier();
    }

    public function testGetUserIdentifierReturnsTheEmail(): void
    {
        $user = (new User())->setEmail('jane@example.com');

        self::assertSame('jane@example.com', $user->getUserIdentifier());
    }

    public function testGetRolesDefaultsToRoleUserWhenNoRolesAreSet(): void
    {
        $user = new User();

        self::assertSame([User::ROLE_USER], $user->getRoles());
    }

    public function testGetRolesReturnsUniqueAssignedRoles(): void
    {
        $user = $this->markAsPersisted(new User());
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN'], array_values($user->getRoles()));
    }

    public function testSetRolesRejectsAnUnknownRole(): void
    {
        $user = $this->markAsPersisted(new User());

        $this->expectException(\InvalidArgumentException::class);

        $user->setRoles(['ROLE_UNKNOWN']);
    }

    public function testSetRolesAllowsCompanyAdminOnANewUser(): void
    {
        $user = new User();

        $user->setRoles(['ROLE_COMPANY_ADMIN']);

        self::assertSame(['ROLE_COMPANY_ADMIN'], $user->getRoles());
    }

    public function testSetRolesRejectsNonCompanyAdminRoleOnANewUser(): void
    {
        $user = new User();

        $this->expectException(BadRequestException::class);

        $user->setRoles(['ROLE_ADMIN']);
    }

    public function testSetRolesAllowsAnyKnownRoleOnAnExistingUser(): void
    {
        $user = $this->markAsPersisted(new User());

        $user->setRoles(['ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN'], $user->getRoles());
    }

    public function testGetRoleReturnsFirstRoleOrDefault(): void
    {
        $user = new User();
        self::assertSame(User::ROLE_USER, $user->getRole());

        $user->setRole('ROLE_COMPANY_ADMIN');
        self::assertSame('ROLE_COMPANY_ADMIN', $user->getRole());
    }

    public function testEraseCredentialsIsANoOp(): void
    {
        $user = new User();

        $user->eraseCredentials();

        $this->addToAssertionCount(1);
    }
}
