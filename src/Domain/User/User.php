<?php

namespace App\Domain\User;

class User
{
    public function __construct(
        public string $email,
        public string $password,
        public array $roles,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phoneNumber,
        public ?string $companyId = null,
        public bool $isActive = false,
        public ?string $emailVerificationToken = null,
        public ?string $id = null,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }
}
