<?php

namespace App\Tests\Infrastructure\Company\Http;

use App\Application\Company\CreateCompany;
use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\SlugGeneratorInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Company\Http\CreateCompanyController;
use App\Infrastructure\Company\Http\CreateCompanyInput;
use App\Infrastructure\Security\Keycloak\ApiUser;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateCompanyControllerTest extends TestCase
{
    private CompanyRepositoryInterface&Stub $companyRepository;
    private UserRepositoryInterface&Stub $userRepository;
    private CreateCompanyController $controller;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createStub(CompanyRepositoryInterface::class);
        $this->userRepository = $this->createStub(UserRepositoryInterface::class);

        $idGenerator = $this->createStub(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('company-id');
        $slugGenerator = $this->createStub(SlugGeneratorInterface::class);
        $slugGenerator->method('slugify')->willReturn('acme-corp');
        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->controller = new CreateCompanyController(new CreateCompany(
            $this->companyRepository,
            $idGenerator,
            $slugGenerator,
            $this->userRepository,
            $transaction,
        ));
    }

    private function input(): CreateCompanyInput
    {
        return new CreateCompanyInput(name: 'Acme Corp');
    }

    private function user(): ApiUser
    {
        return new ApiUser('keycloak-id', 'user-id', null, ['ROLE_USER', 'ROLE_COMPANY_ADMIN']);
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

    public function testReturnsConflictWhenTheUserAlreadyHasACompany(): void
    {
        $this->userRepository->method('findById')->willReturn(
            new User('keycloak-id', 'existing-company-id', 'user-id'),
        );

        $response = ($this->controller)($this->input(), $this->user());

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testReturnsServerErrorWhenSavingFails(): void
    {
        $this->companyRepository->method('save')->willThrowException(new \RuntimeException('database down'));

        $response = ($this->controller)($this->input(), $this->user());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testReturnsCreatedWithTheCompanyOfTheSignedInUser(): void
    {
        $this->userRepository->method('attachToCompany')->willReturn(true);

        $response = ($this->controller)($this->input(), $this->user());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame(['id' => 'company-id', 'slug' => 'acme-corp'], $this->body($response)['payload'] ?? null);
    }
}
