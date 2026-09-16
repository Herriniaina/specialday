<?php

namespace App\Infrastructure\User\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class ChangePasswordInput
{
    public function __construct(
        #[SerializedName('current_password')]
        #[Assert\NotBlank(message: 'user.current_password.not_blank')]
        public string $currentPassword = '',

        #[SerializedName('new_password')]
        #[Assert\NotBlank(message: 'user.new_password.not_blank')]
        #[Assert\Length(min: 8, minMessage: 'user.new_password.too_short')]
        public string $newPassword = '',
    ) {
    }
}
