<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Application\User\ChangePassword;
use App\Application\User\PasswordHasherInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\Entity\User as UserEntity;
use App\Infrastructure\User\Http\ChangePasswordController;
use App\Infrastructure\User\Http\ChangePasswordInput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ChangePasswordControllerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&Stub $passwordHasher;
    private ChangePasswordController $controller;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createStub(PasswordHasherInterface::class);

        $this->controller = new ChangePasswordController(new ChangePassword(
            $this->userRepository,
            $this->passwordHasher,
        ));
    }

    private function input(): ChangePasswordInput
    {
        return new ChangePasswordInput(currentPassword: 'old-password', newPassword: 'new-password');
    }

    private function currentUser(): UserEntity
    {
        $entity = new UserEntity();
        $property = new \ReflectionProperty(UserEntity::class, 'id');
        $property->setValue($entity, Uuid::uuid7());

        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    private function body(JsonResponse $response): array
    {
        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);

        return $body;
    }

    public function testReturnsNotFoundWhenTheUserDoesNotExist(): void
    {
        $this->userRepository->expects(self::once())->method('findById')->willReturn(null);

        $response = ($this->controller)($this->input(), $this->currentUser());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testReturnsBadRequestWhenTheCurrentPasswordIsWrong(): void
    {
        $currentUser = $this->currentUser();

        $this->userRepository->method('findById')->willReturn(new User(
            email: 'jane@example.com',
            password: 'hashed-old-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
            id: (string) $currentUser->getId(),
        ));
        $this->passwordHasher->method('verify')->willReturn(false);
        $this->userRepository->expects(self::never())->method('updatePassword');

        $response = ($this->controller)($this->input(), $currentUser);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReturnsSuccessAndUpdatesTheHashedPassword(): void
    {
        $currentUser = $this->currentUser();

        $this->userRepository->method('findById')->willReturn(new User(
            email: 'jane@example.com',
            password: 'hashed-old-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
            id: (string) $currentUser->getId(),
        ));
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->userRepository->expects(self::once())
            ->method('updatePassword')
            ->with((string) $currentUser->getId(), 'hashed-new-password');

        $response = ($this->controller)($this->input(), $currentUser);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsServerErrorOnAnUnexpectedException(): void
    {
        $this->userRepository->expects(self::once())->method('findById')->willThrowException(new \RuntimeException('database down'));

        $response = ($this->controller)($this->input(), $this->currentUser());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }
}
