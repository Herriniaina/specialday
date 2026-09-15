<?php

namespace App\Infrastructure\User\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RegisterUserInput
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        #[Assert\NotBlank(message: 'user.email.not_blank')]
        #[Assert\Email(message: 'user.email.invalid')]
        public string $email = '',

        #[Assert\NotBlank(message: 'user.password.not_blank')]
        #[Assert\Length(min: 8, minMessage: 'user.password.too_short')]
        public string $password = '',

        #[Assert\NotNull]
        #[Assert\All([
            new Assert\Type(type: 'string'),
            new Assert\Choice(
                choices: ['ROLE_USER', 'ROLE_COMPANY_ADMIN', 'ROLE_ADMIN'],
                message: 'user.roles.invalid',
            ),
        ])]
        public array $roles = ['ROLE_COMPANY_ADMIN'],

        #[SerializedName('first_name')]
        public string $firstName = '',

        #[SerializedName('last_name')]
        public string $lastName = '',

        #[SerializedName('phone_number')]
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{1,14}$/',
            message: 'user.phone.invalid',
        )]
        public string $phoneNumber = '',

        #[SerializedName('company_id')]
        #[Assert\Uuid(message: 'company.id.invalid')]
        public ?string $companyId = null,
    ) {
    }
}
