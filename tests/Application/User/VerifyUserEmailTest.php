<?php

namespace App\Tests\Application\User;

use App\Application\User\InvalidEmailVerificationTokenException;
use App\Application\User\UserIdentifierMissingException;
use App\Application\User\VerifyUserEmail;
use App\Application\User\VerifyUserEmailRequest;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerifyUserEmailTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private VerifyUserEmail $verifyUserEmail;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->verifyUserEmail = new VerifyUserEmail($this->userRepository);
    }

    public function testExecuteThrowsWhenTokenDoesNotMatchAnyUser(): void
    {
        $this->userRepository->expects(self::once())
            ->method('findByEmailVerificationToken')
            ->with('unknown-token')
            ->willReturn(null);

        $this->userRepository->expects(self::never())->method('activate');

        $this->expectException(InvalidEmailVerificationTokenException::class);

        $this->verifyUserEmail->execute(new VerifyUserEmailRequest('unknown-token'));
    }

    public function testExecuteThrowsWhenMatchedUserHasNoIdentifier(): void
    {
        $user = new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: [],
            firstName: null,
            lastName: null,
            phoneNumber: null,
            id: null,
        );

        $this->userRepository->method('findByEmailVerificationToken')->willReturn($user);

        $this->userRepository->expects(self::never())->method('activate');

        $this->expectException(UserIdentifierMissingException::class);

        $this->verifyUserEmail->execute(new VerifyUserEmailRequest('token'));
    }

    public function testExecuteActivatesMatchedUser(): void
    {
        $user = new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: [],
            firstName: null,
            lastName: null,
            phoneNumber: null,
            id: 'user-id',
        );

        $this->userRepository->expects(self::once())
            ->method('findByEmailVerificationToken')
            ->with('valid-token')
            ->willReturn($user);

        $this->userRepository->expects(self::once())->method('activate')->with('user-id');

        $this->verifyUserEmail->execute(new VerifyUserEmailRequest('valid-token'));
    }
}
