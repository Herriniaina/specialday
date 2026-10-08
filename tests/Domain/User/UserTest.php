<?php

namespace App\Tests\Domain\User;

use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testConstructorAndGettersExposeProvidedValues(): void
    {
        $user = new User(identityProviderId: 'keycloak-id', companyId: 'company-id', id: 'user-id');

        self::assertSame('user-id', $user->getId());
        self::assertSame('keycloak-id', $user->getIdentityProviderId());
        self::assertSame('company-id', $user->getCompanyId());
    }

    public function testDefaultsAreAppliedWhenOptionalArgumentsAreOmitted(): void
    {
        $user = new User(identityProviderId: 'keycloak-id');

        self::assertNull($user->getId());
        self::assertNull($user->getCompanyId());
    }
}
