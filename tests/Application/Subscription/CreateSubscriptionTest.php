<?php

namespace App\Tests\Application\Subscription;

use App\Application\Subscription\CreateSubscription;
use App\Application\Subscription\CreateSubscriptionRequest;
use App\Application\Subscription\SubscriptionAlreadyExistsException;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Subscription\PaymentMethodEnum;
use App\Domain\Subscription\PlanEnum;
use App\Domain\Subscription\SubscriptionRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateSubscriptionTest extends TestCase
{
    private SubscriptionRepositoryInterface&MockObject $subscriptionRepository;
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private CreateSubscription $createSubscription;

    protected function setUp(): void
    {
        $this->subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->createSubscription = new CreateSubscription($this->subscriptionRepository, $this->companyRepository);
    }

    public function testExecuteThrowsWhenCompanyDoesNotExist(): void
    {
        $this->companyRepository->expects(self::once())->method('existsById')->with('company-id')->willReturn(false);

        $this->subscriptionRepository->expects(self::never())->method('existsByCompanyId');
        $this->subscriptionRepository->expects(self::never())->method('create');

        $this->expectException(CompanyNotFoundException::class);

        $this->createSubscription->execute(new CreateSubscriptionRequest('company-id', PlanEnum::FREE, null));
    }

    public function testExecuteThrowsWhenCompanyAlreadyHasASubscription(): void
    {
        $this->companyRepository->expects(self::once())->method('existsById')->with('company-id')->willReturn(true);
        $this->subscriptionRepository->expects(self::once())->method('existsByCompanyId')->with('company-id')->willReturn(true);

        $this->subscriptionRepository->expects(self::never())->method('create');

        $this->expectException(SubscriptionAlreadyExistsException::class);

        $this->createSubscription->execute(new CreateSubscriptionRequest('company-id', PlanEnum::FREE, null));
    }

    public function testExecuteCreatesTheSubscriptionAndReturnsItsId(): void
    {
        $this->companyRepository->expects(self::once())->method('existsById')->with('company-id')->willReturn(true);
        $this->subscriptionRepository->expects(self::once())->method('existsByCompanyId')->with('company-id')->willReturn(false);

        $this->subscriptionRepository->expects(self::once())
            ->method('create')
            ->with(
                'company-id',
                PlanEnum::STANDARD,
                PaymentMethodEnum::MOBILE_MONEY,
                self::isInstanceOf(\DateTimeImmutable::class),
            )
            ->willReturn('subscription-id');

        $id = $this->createSubscription->execute(new CreateSubscriptionRequest(
            'company-id',
            PlanEnum::STANDARD,
            PaymentMethodEnum::MOBILE_MONEY,
        ));

        self::assertSame('subscription-id', $id);
    }

    public function testExecuteAllowsANullPaymentMethod(): void
    {
        $this->companyRepository->expects(self::once())->method('existsById')->willReturn(true);
        $this->subscriptionRepository->expects(self::once())->method('existsByCompanyId')->willReturn(false);

        $this->subscriptionRepository->expects(self::once())
            ->method('create')
            ->with('company-id', PlanEnum::FREE, null, self::isInstanceOf(\DateTimeImmutable::class))
            ->willReturn('subscription-id');

        $id = $this->createSubscription->execute(new CreateSubscriptionRequest('company-id', PlanEnum::FREE, null));

        self::assertSame('subscription-id', $id);
    }
}
