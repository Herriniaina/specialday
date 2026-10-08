<?php

namespace App\Domain\User;

/**
 * Link between an identity provider account (which holds email, name, phone, credentials and
 * roles) and the company the user works for.
 */
class User
{
    public function __construct(
        public string $identityProviderId,
        public ?string $companyId = null,
        public ?string $id = null,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIdentityProviderId(): string
    {
        return $this->identityProviderId;
    }

    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }
}
