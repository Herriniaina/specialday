<?php

namespace App\Tests\Application\User;

use App\Application\Shared\TransactionInterface;
use App\Application\User\EmailAlreadyExistsException;
use App\Application\User\IdentityProviderInterface;
use App\Application\User\RegisterUser;
use App\Application\User\RegisterUserRequest;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegisterUserTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private IdentityProviderInterface&MockObject $identityProvider;
    private RegisterUser $registerUser;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->identityProvider = $this->createMock(IdentityProviderInterface::class);

        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->registerUser = new RegisterUser($this->userRepository, $this->identityProvider, $transaction);
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
        );
    }

    public function testExecuteThrowsWhenEmailAlreadyExists(): void
    {
        $this->identityProvider->expects(self::once())
            ->method('createAccount')
            ->willThrowException(new EmailAlreadyExistsException('Email already exist.'));

        $this->userRepository->expects(self::never())->method('save');
        $this->identityProvider->expects(self::never())->method('deleteAccount');

        $this->expectException(EmailAlreadyExistsException::class);

        $this->registerUser->execute($this->validRequest());
    }

    public function testExecuteCreatesTheAccountSavesTheLinkedProfileAndSendsTheVerificationEmail(): void
    {
        $this->identityProvider->expects(self::once())
            ->method('createAccount')
            ->with('jane@example.com', 'plain-password', 'Jane', 'Doe', '+261340000000', ['ROLE_COMPANY_ADMIN'])
            ->willReturn('keycloak-id');

        $this->userRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (User $user): bool {
                self::assertSame('keycloak-id', $user->getIdentityProviderId());
                self::assertNull($user->getCompanyId());

                return true;
            }));

        $this->identityProvider->expects(self::once())->method('sendEmailVerification')->with('keycloak-id');
        $this->identityProvider->expects(self::never())->method('deleteAccount');

        $this->registerUser->execute($this->validRequest());
    }

    public function testExecuteDeletesTheAccountWhenTheProfileCannotBeSaved(): void
    {
        $this->identityProvider->method('createAccount')->willReturn('keycloak-id');
        $this->userRepository->expects(self::once())->method('save')->willThrowException(new \RuntimeException('database down'));

        $this->identityProvider->expects(self::never())->method('sendEmailVerification');
        $this->identityProvider->expects(self::once())->method('deleteAccount')->with('keycloak-id');

        $this->expectExceptionObject(new \RuntimeException('database down'));

        $this->registerUser->execute($this->validRequest());
    }

    public function testExecuteDeletesTheAccountWhenTheVerificationEmailCannotBeSent(): void
    {
        $this->identityProvider->method('createAccount')->willReturn('keycloak-id');
        $this->identityProvider->method('sendEmailVerification')->willThrowException(new \RuntimeException('smtp down'));
        $this->userRepository->expects(self::once())->method('save');

        $this->identityProvider->expects(self::once())->method('deleteAccount')->with('keycloak-id');

        $this->expectExceptionObject(new \RuntimeException('smtp down'));

        $this->registerUser->execute($this->validRequest());
    }

    public function testAnEmptyPhoneNumberIsNotSentToTheIdentityProvider(): void
    {
        $this->identityProvider->expects(self::once())
            ->method('createAccount')
            ->with('jane@example.com', 'plain-password', 'Jane', 'Doe', null, ['ROLE_COMPANY_ADMIN'])
            ->willReturn('keycloak-id');
        $this->userRepository->expects(self::once())->method('save');

        $this->registerUser->execute(new RegisterUserRequest(
            email: 'jane@example.com',
            password: 'plain-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '',
        ));
    }
}
