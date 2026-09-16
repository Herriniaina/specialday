<?php

namespace App\Tests\Application\User;

use App\Application\User\ChangePassword;
use App\Application\User\ChangePasswordRequest;
use App\Application\User\InvalidCurrentPasswordException;
use App\Application\User\PasswordHasherInterface;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangePasswordTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private ChangePassword $changePassword;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $this->changePassword = new ChangePassword($this->userRepository, $this->passwordHasher);
    }

    private function existingUser(): User
    {
        return new User(
            email: 'jane@example.com',
            password: 'hashed-current-password',
            roles: [],
            firstName: null,
            lastName: null,
            phoneNumber: null,
            id: 'user-id',
        );
    }

    public function testExecuteThrowsWhenUserDoesNotExist(): void
    {
        $this->userRepository->expects(self::once())
            ->method('findById')
            ->with('user-id')
            ->willReturn(null);

        $this->passwordHasher->expects(self::never())->method('verify');
        $this->userRepository->expects(self::never())->method('updatePassword');

        $this->expectException(UserNotFoundException::class);

        $this->changePassword->execute(new ChangePasswordRequest('user-id', 'current', 'new-password'));
    }

    public function testExecuteThrowsWhenCurrentPasswordIsIncorrect(): void
    {
        $this->userRepository->expects(self::once())
            ->method('findById')
            ->with('user-id')
            ->willReturn($this->existingUser());

        $this->passwordHasher->expects(self::once())
            ->method('verify')
            ->with('hashed-current-password', 'wrong-password')
            ->willReturn(false);

        $this->userRepository->expects(self::never())->method('updatePassword');

        $this->expectException(InvalidCurrentPasswordException::class);

        $this->changePassword->execute(new ChangePasswordRequest('user-id', 'wrong-password', 'new-password'));
    }

    public function testExecuteHashesAndPersistsTheNewPassword(): void
    {
        $this->userRepository->expects(self::once())
            ->method('findById')
            ->with('user-id')
            ->willReturn($this->existingUser());

        $this->passwordHasher->expects(self::once())
            ->method('verify')
            ->with('hashed-current-password', 'current-password')
            ->willReturn(true);

        $this->passwordHasher->expects(self::once())
            ->method('hash')
            ->with('new-password')
            ->willReturn('hashed-new-password');

        $this->userRepository->expects(self::once())
            ->method('updatePassword')
            ->with('user-id', 'hashed-new-password');

        $this->changePassword->execute(new ChangePasswordRequest('user-id', 'current-password', 'new-password'));
    }
}
