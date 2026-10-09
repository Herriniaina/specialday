<?php

namespace App\Tests\Infrastructure\Subscription\Http;

use App\Application\Subscription\CreateSubscription;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use App\Infrastructure\Subscription\Http\CreateSubscriptionController;
use App\Infrastructure\Subscription\Http\CreateSubscriptionInput;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateSubscriptionControllerTest extends TestCase
{
    private SubscriptionRepositoryInterface&Stub $subscriptionRepository;
    private CompanyRepositoryInterface&Stub $companyRepository;
    private CreateSubscriptionController $controller;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createStub(SubscriptionRepositoryInterface::class);
        $this->companyRepository = $this->createStub(CompanyRepositoryInterface::class);

        $this->controller = new CreateSubscriptionController(new CreateSubscription(
            $this->subscriptionRepository,
            $this->companyRepository,
        ));
    }

    private function input(): CreateSubscriptionInput
    {
        return new CreateSubscriptionInput(plan: 'standard', paymentMethod: 'stripe');
    }

    private function company(): Company
    {
        return new Company('company-id', 'Photo Company', 'photo-company', new \DateTimeImmutable());
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

    public function testReturnsNotFoundWhenTheCompanyDoesNotExist(): void
    {
        $this->companyRepository->method('existsById')->willReturn(false);

        $response = ($this->controller)($this->input(), $this->company());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testReturnsConflictWhenTheCompanyAlreadyHasASubscription(): void
    {
        $this->companyRepository->method('existsById')->willReturn(true);
        $this->subscriptionRepository->method('existsByCompanyId')->willReturn(true);

        $response = ($this->controller)($this->input(), $this->company());

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testReturnsServerErrorOnAnUnexpectedException(): void
    {
        $this->companyRepository->method('existsById')->willReturn(true);
        $this->subscriptionRepository->method('existsByCompanyId')->willReturn(false);
        $this->subscriptionRepository->method('create')->willThrowException(new \RuntimeException('database down'));

        $response = ($this->controller)($this->input(), $this->company());

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testReturnsCreatedWithTheSubscriptionId(): void
    {
        $this->companyRepository->method('existsById')->willReturn(true);
        $this->subscriptionRepository->method('existsByCompanyId')->willReturn(false);
        $this->subscriptionRepository->method('create')->willReturnCallback(
            // The subscription goes to the subdomain's company: there is no company_id in the payload anymore.
            static fn (string $companyId): string => 'company-id' === $companyId ? 'subscription-id' : 'wrong-company',
        );

        $response = ($this->controller)($this->input(), $this->company());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame(['id' => 'subscription-id'], $this->body($response)['payload']);
    }

    public function testAcceptsAMissingPaymentMethod(): void
    {
        $this->companyRepository->method('existsById')->willReturn(true);
        $this->subscriptionRepository->method('existsByCompanyId')->willReturn(false);
        $this->subscriptionRepository->method('create')->willReturn('subscription-id');

        $response = ($this->controller)(new CreateSubscriptionInput(plan: 'free', paymentMethod: null), $this->company());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }
}
