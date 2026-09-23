<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Application\User\PasswordHasherInterface;
use App\Application\User\RegisterUser;
use App\Application\User\UserNotifierInterface;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\Http\RegisterUserController;
use App\Infrastructure\User\Http\RegisterUserInput;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RegisterUserControllerTest extends TestCase
{
    private UserRepositoryInterface&Stub $userRepository;
    private PasswordHasherInterface&Stub $passwordHasher;
    private UserNotifierInterface&Stub $notifier;
    private RegisterUserController $controller;

    protected function setUp(): void
    {
        $this->userRepository = $this->createStub(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createStub(PasswordHasherInterface::class);
        $this->notifier = $this->createStub(UserNotifierInterface::class);

        $this->controller = new RegisterUserController(new RegisterUser(
            $this->userRepository,
            $this->passwordHasher,
            $this->notifier,
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
            companyId: 'company-id',
        );
    }

    public function testReturnsConflictWhenTheEmailAlreadyExists(): void
    {
        $this->userRepository->method('existsByEmail')->willReturn(true);

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testReturnsServerErrorOnAnUnexpectedException(): void
    {
        $this->userRepository->method('existsByEmail')->willReturn(false);
        $this->passwordHasher->method('hash')->willThrowException(new \RuntimeException('hashing failed'));

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testReturnsCreatedWhenTheUserIsRegistered(): void
    {
        $this->userRepository->method('existsByEmail')->willReturn(false);
        $this->passwordHasher->method('hash')->willReturn('hashed-password');

        $response = ($this->controller)($this->input());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }
}
