<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Application\User\VerifyUserEmail;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\Http\VerifyUserEmailController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class VerifyUserEmailControllerTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private VerifyUserEmailController $controller;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->controller = new VerifyUserEmailController(new VerifyUserEmail($this->userRepository));
    }

    public function testReturnsBadRequestWhenTheTokenIsInvalid(): void
    {
        $this->userRepository->method('findByEmailVerificationToken')->willReturn(null);
        $this->userRepository->expects(self::never())->method('activate');

        $response = ($this->controller)('invalid-token');

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testReturnsSuccessAndActivatesTheUser(): void
    {
        $this->userRepository->method('findByEmailVerificationToken')->willReturn(new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
            id: 'user-id',
        ));
        $this->userRepository->expects(self::once())->method('activate')->with('user-id');

        $response = ($this->controller)('valid-token');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsServerErrorWhenTheUserHasNoIdentifier(): void
    {
        $this->userRepository->method('findByEmailVerificationToken')->willReturn(new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: ['ROLE_COMPANY_ADMIN'],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
            id: null,
        ));
        $this->userRepository->expects(self::never())->method('activate');

        $response = ($this->controller)('valid-token');

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }
}
