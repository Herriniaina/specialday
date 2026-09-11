<?php

namespace App\Infrastructure\Company\Http;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateCompanyInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'company.name.not_blank')]
        #[Assert\Length(max: 255, maxMessage: 'company.name.too_long')]
        public string $name = '',

        #[Assert\NotBlank(message: 'company.email.not_blank')]
        #[Assert\Email(message: 'company.email.invalid')]
        public string $email = '',

        #[SerializedName('user_id')]
        #[Assert\NotBlank(message: 'company.user_id.not_blank')]
        #[Assert\Uuid(message: 'company.user_id.invalid')]
        public string $userId = '',

        #[Assert\Length(max: 255, maxMessage: 'company.nif.too_long')]
        public ?string $nif = null,

        #[Assert\Length(max: 255, maxMessage: 'company.stat.too_long')]
        public ?string $stat = null,

        #[Assert\Length(max: 255, maxMessage: 'company.rcs.too_long')]
        public ?string $rcs = null,

        #[Assert\Length(max: 15, maxMessage: 'company.phone.too_long')]
        public ?string $phone = null,

        #[SerializedName('instagram_profile')]
        #[Assert\Length(max: 255, maxMessage: 'company.instagram_profile.too_long')]
        public ?string $instagramProfile = null,

        #[SerializedName('facebook_profile')]
        #[Assert\Length(max: 255, maxMessage: 'company.facebook_profile.too_long')]
        public ?string $facebookProfile = null,

        #[SerializedName('youtube_profile')]
        #[Assert\Length(max: 255, maxMessage: 'company.youtube_profile.too_long')]
        public ?string $youtubeProfile = null,
    ) {
    }
}
