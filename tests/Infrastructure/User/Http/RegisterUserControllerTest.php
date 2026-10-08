<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Application\Shared\TransactionInterface;
use App\Application\User\AccountRejectedException;
use App\Application\User\EmailAlreadyExistsException;
use App\Application\User\IdentityProviderInterface;
use App\Application\User\RegisterUser;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\Http\RegisterUserController;
use App\Infrastructure\User\Http\RegisterUserInput;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RegisterUserControllerTest extends TestCase
{
    private UserRepositoryInterface&Stub $userRepository;
    private IdentityProviderInterface&Stub $identityProvider;
    private RegisterUserController $controller;

    protected function setUp(): void
    {
        $this->userRepository = $this->createStub(UserRepositoryInterface::class);
        $this->identityProvider = $this->createStub(IdentityProviderInterface::class);

        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->controller = new RegisterUserController(new RegisterUser(
            $this->userRepository,
            $this->identityProvider,
            $transaction,
        ));
    }

    private function input(): RegisterUserInput
    {
        return new RegisterUserInput(
            email: 'jane@example.com',
            password: 'plain-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
        );
    }

    public function testReturnsConflictWhenTheEmailAlreadyExists(): void
    {
        $this->identityProvider->method('createAccount')->willThrowException(new EmailAlreadyExistsException('Email already exist.'));

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testReturnsBadRequestWhenKeycloakRejectsTheAccount(): void
    {
        $this->identityProvider->method('createAccount')->willThrowException(new AccountRejectedException('invalidPasswordNotEmailMessage'));

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('invalidPasswordNotEmailMessage', json_decode((string) $response->getContent(), true)['statusText'] ?? null);
    }

    public function testReturnsServerErrorOnAnUnexpectedException(): void
    {
        $this->identityProvider->method('createAccount')->willThrowException(new \RuntimeException('keycloak down'));

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testReturnsCreatedWhenTheUserIsRegistered(): void
    {
        $this->identityProvider->method('createAccount')->willReturn('keycloak-id');

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }
}
