<?php

namespace App\Tests\Domain\User;

use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testConstructorAndGettersExposeProvidedValues(): void
    {
        $user = new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
            companyId: 'company-id',
            isActive: true,
            emailVerificationToken: 'token',
            id: 'user-id',
        );

        self::assertSame('user-id', $user->getId());
        self::assertSame('jane@example.com', $user->getEmail());
        self::assertSame(['ROLE_COMPANY_ADMIN'], $user->getRoles());
        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
        self::assertSame('+261340000000', $user->getPhoneNumber());
        self::assertSame('company-id', $user->getCompanyId());
        self::assertTrue($user->isActive());
        self::assertSame('token', $user->getEmailVerificationToken());
    }

    public function testDefaultsAreAppliedWhenOptionalArgumentsAreOmitted(): void
    {
        $user = new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: [],
            firstName: null,
            lastName: null,
            phoneNumber: null,
        );

        self::assertNull($user->getId());
        self::assertNull($user->getCompanyId());
        self::assertFalse($user->isActive());
        self::assertNull($user->getEmailVerificationToken());
    }
}
