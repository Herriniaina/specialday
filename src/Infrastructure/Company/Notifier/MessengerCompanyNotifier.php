<?php

namespace App\Infrastructure\Company\Notifier;

use App\Application\Company\CompanyNotifierInterface;
use App\Domain\Company\Company;
use App\Infrastructure\Company\Message\SendCompanyWelcomeEmail;
use Override;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerCompanyNotifier implements CompanyNotifierInterface
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    #[Override]
    public function companyRegistered(Company $company): void
    {
        $this->bus->dispatch(new SendCompanyWelcomeEmail(
            companyName: $company->name,
            email: $company->email,
            token: $company->token,
            accessCode: $company->accessCode,
        ));
    }
}
