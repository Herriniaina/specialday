<?php

namespace App\Tests\Application\User;

use App\Application\User\EmailAlreadyExistsException;
use App\Application\User\PasswordHasherInterface;
use App\Application\User\RegisterUser;
use App\Application\User\RegisterUserRequest;
use App\Application\User\UserNotifierInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class RegisterUserTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&Stub $passwordHasher;
    private UserNotifierInterface&MockObject $notifier;
    private RegisterUser $registerUser;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $this->notifier = $this->createMock(UserNotifierInterface::class);

        $this->registerUser = new RegisterUser(
            $this->userRepository,
            $this->passwordHasher,
            $this->notifier,
        );
    }

    private function validRequest(): RegisterUserRequest
    {
        return new RegisterUserRequest(
            email: 'jane@example.com',
            password: 'plain-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
            companyId: 'company-id',
        );
    }

    public function testExecuteThrowsWhenEmailAlreadyExists(): void
    {
        $this->userRepository->expects(self::once())
            ->method('existsByEmail')
            ->with('jane@example.com')
            ->willReturn(true);

        $this->userRepository->expects(self::never())->method('save');
        $this->notifier->expects(self::never())->method('emailVerificationRequested');

        $this->expectException(EmailAlreadyExistsException::class);

        $this->registerUser->execute($this->validRequest());
    }

    public function testExecuteHashesPasswordSavesUserAndNotifies(): void
    {
        $this->userRepository->expects(self::once())
            ->method('existsByEmail')
            ->with('jane@example.com')
            ->willReturn(false);

        $this->passwordHasher->method('hash')->willReturnCallback(function (string $plainPassword): string {
            self::assertSame('plain-password', $plainPassword);

            return 'hashed-password';
        });

        $this->userRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (User $user): bool {
                self::assertSame('jane@example.com', $user->getEmail());
                self::assertSame('hashed-password', $user->password);
                self::assertSame(['ROLE_COMPANY_ADMIN'], $user->getRoles());
                self::assertFalse($user->isActive());
                self::assertNotNull($user->getEmailVerificationToken());

                return true;
            }));

        $this->notifier->expects(self::once())
            ->method('emailVerificationRequested')
            ->with(
                self::isInstanceOf(User::class),
                self::callback(static fn (string $token): bool => '' !== $token),
            );

        $this->registerUser->execute($this->validRequest());
    }
}
