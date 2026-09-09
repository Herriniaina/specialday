<?php

namespace App\Application\User;

use Symfony\Component\Validator\Constraints as Assert;

readonly class RegisterUserRequest
{
    public function __construct(
        public string $id,

        #[Assert\NotBlank(message: "user.email.not_blank")]
        #[Assert\Email(message: "user.email.invalid")]
        public string $email,

        #[Assert\NotBlank(message: "user.password.not_blank")]
        #[Assert\Length(
            min: 8, 
            minMessage: "user.password.too_short"
        )]
        public string $password,

        #[Assert\NotNull]
        #[Assert\All([
            new Assert\Type(type: 'string'),
            new Assert\Choice(
                choices: ['ROLE_USER', 'ROLE_COMPANY_ADMIN', 'ROLE_ADMIN'], 
                message: "user.roles.invalid"
            )
        ])]
        public array $roles,
        public string $firstName,
        public string $lastName,

        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{1,14}$/', 
            message: "user.phone.invalid"
        )]
        public string $phoneNumber
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            id: $payload['id'] ?? '',
            email: $payload['email'] ?? '',
            password: $payload['password'] ?? '',
            roles: $payload['roles'] ?? ['ROLE_COMPANY_ADMIN'],
            firstName: $payload['first_name'] ?? '',
            lastName: $payload['last_name'] ?? '',
            phoneNumber: $payload['phone_number'] ?? ''
        );
    }
}
