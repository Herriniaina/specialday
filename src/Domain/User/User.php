<?php

namespace App\Domain\User;

class User
{
    private ?string $id = null;

    public function __construct(
        public string $email,
        public string $password,
        public array $roles,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phoneNumber)
    {}

    public function getId(): ?string
    {
        return $this->id?->toString();
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

    public function getRoles(): array
    {
        return $this->roles;
    }
}