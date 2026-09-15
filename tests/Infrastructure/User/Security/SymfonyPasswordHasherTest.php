<?php

namespace App\Tests\Infrastructure\User\Security;

use App\Infrastructure\User\Entity\User;
use App\Infrastructure\User\Security\SymfonyPasswordHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface as SymfonyPasswordHasherInterface;

class SymfonyPasswordHasherTest extends TestCase
{
    public function testHashDelegatesToTheHasherResolvedForTheUserEntity(): void
    {
        $symfonyHasher = $this->createMock(SymfonyPasswordHasherInterface::class);
        $symfonyHasher->expects(self::once())
            ->method('hash')
            ->with('plain-password')
            ->willReturn('hashed-password');

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->expects(self::once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($symfonyHasher);

        $hasher = new SymfonyPasswordHasher($factory);

        self::assertSame('hashed-password', $hasher->hash('plain-password'));
    }
}
