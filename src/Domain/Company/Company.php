<?php

namespace App\Domain\Company;

class Company
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public \DateTimeImmutable $createdAt,
        public string $accessCode,
        public string $token,
        public string $userId,
        public ?string $nif = null,
        public ?string $stat = null,
        public ?string $rcs = null,
        public ?string $phone = null,
        public ?string $instagramProfile = null,
        public ?string $facebookProfile = null,
        public ?string $youtubeProfile = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAccessCode(): string
    {
        return $this->accessCode;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }

    public function getStat(): ?string
    {
        return $this->stat;
    }

    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getInstagramProfile(): ?string
    {
        return $this->instagramProfile;
    }

    public function getFacebookProfile(): ?string
    {
        return $this->facebookProfile;
    }

    public function getYoutubeProfile(): ?string
    {
        return $this->youtubeProfile;
    }
}
