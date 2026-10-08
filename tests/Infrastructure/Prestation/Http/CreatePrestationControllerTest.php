<?php

namespace App\Tests\Infrastructure\Prestation\Http;

use App\Application\Prestation\CreatePrestation;
use App\Application\Shared\IdGeneratorInterface;
use App\Domain\Prestation\Prestation;
use App\Domain\Prestation\PrestationRepositoryInterface;
use App\Domain\PrestationCategory\PrestationCategoryRepositoryInterface;
use App\Infrastructure\Prestation\Http\CreatePrestationController;
use App\Infrastructure\Prestation\Http\CreatePrestationInput;
use App\Infrastructure\Prestation\Http\PriceInput;
use App\Infrastructure\Security\Keycloak\ApiUser;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreatePrestationControllerTest extends TestCase
{
    private PrestationRepositoryInterface&Stub $prestationRepository;
    private PrestationCategoryRepositoryInterface&Stub $prestationCategoryRepository;
    private CreatePrestationController $controller;

    protected function setUp(): void
    {
        $this->prestationRepository = $this->createStub(PrestationRepositoryInterface::class);
        $this->prestationCategoryRepository = $this->createStub(PrestationCategoryRepositoryInterface::class);
        $idGenerator = $this->createStub(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn('generated-id');

        $this->controller = new CreatePrestationController(new CreatePrestation(
            $this->prestationRepository,
            $this->prestationCategoryRepository,
            $idGenerator,
        ));
    }

    private function input(): CreatePrestationInput
    {
        return new CreatePrestationInput(
            prestationCategoryId: 'category-id',
            price: new PriceInput(amount: 1000, currency: 'Ar', taxIncluded: true),
            quantity: 1,
            unit: 'photo',
            notes: 'Bring extra lenses.',
        );
    }

    private function companyAdmin(): ApiUser
    {
        return new ApiUser('keycloak-id', 'user-id', 'company-id', ['ROLE_USER', 'ROLE_COMPANY_ADMIN']);
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

    public function testReturnsForbiddenWhenUserHasNoCompany(): void
    {
        $response = ($this->controller)($this->input(), new ApiUser('keycloak-id', 'user-id', null, ['ROLE_USER']));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('prestation.company.required', $this->body($response)['statusText']);
    }

    public function testReturnsNotFoundWhenPrestationCategoryDoesNotExist(): void
    {
        $this->prestationCategoryRepository->method('existsById')->willReturn(false);

        $response = ($this->controller)($this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('Prestation category not found.', $this->body($response)['statusText']);
    }

    public function testReturnsServerErrorWhenSavingFails(): void
    {
        $this->prestationCategoryRepository->method('existsById')->willReturn(true);
        $this->prestationRepository->method('save')->willThrowException(new \RuntimeException('database down'));

        $response = ($this->controller)($this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testReturnsCreatedWithThePrestationIdForTheUserCompany(): void
    {
        $saved = null;
        $this->prestationCategoryRepository->method('existsById')->willReturn(true);
        $this->prestationRepository->method('save')->willReturnCallback(
            function (Prestation $prestation) use (&$saved): void {
                $saved = $prestation;
            }
        );

        $response = ($this->controller)($this->input(), $this->companyAdmin());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame(['id' => 'generated-id'], $this->body($response)['payload']);
        self::assertInstanceOf(Prestation::class, $saved);
        self::assertSame('company-id', $saved->companyId);
        self::assertSame('Bring extra lenses.', $saved->notes);
    }
}
