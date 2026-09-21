<?php

namespace App\Application\Subscription;

use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Subscription\SubscriptionRepositoryInterface;

class CreateSubscription
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private CompanyRepositoryInterface $companyRepository,
    ) {
    }

    public function execute(CreateSubscriptionRequest $request): string
    {
        if (!$this->companyRepository->existsById($request->companyId)) {
            throw new CompanyNotFoundException('Company not found.');
        }

        if ($this->subscriptionRepository->existsByCompanyId($request->companyId)) {
            throw new SubscriptionAlreadyExistsException('Company already has a subscription.');
        }

        return $this->subscriptionRepository->create(
            $request->companyId,
            $request->plan,
            $request->paymentMethod,
            new \DateTimeImmutable(),
        );
    }
}
