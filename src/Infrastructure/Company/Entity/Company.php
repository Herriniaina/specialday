<?php

namespace App\Infrastructure\Company\Entity;

use App\Infrastructure\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_company_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_company_token', columns: ['token'])]
class Company
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'company.name.not_blank')]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'company.email.not_blank')]
    #[Assert\Email(message: 'company.email.invalid')]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 6)]
    private ?string $accessCode = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nif = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rcs = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagramProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookProfile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $youtubeProfile = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAccessCode(): ?string
    {
        return $this->accessCode;
    }

    public function setAccessCode(string $accessCode): static
    {
        $this->accessCode = $accessCode;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNif(): ?string
    {
        return $this->nif;
    }

    public function setNif(?string $nif): static
    {
        $this->nif = $nif;

        return $this;
    }

    public function getStat(): ?string
    {
        return $this->stat;
    }

    public function setStat(?string $stat): static
    {
        $this->stat = $stat;

        return $this;
    }

    public function getRcs(): ?string
    {
        return $this->rcs;
    }

    public function setRcs(?string $rcs): static
    {
        $this->rcs = $rcs;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getInstagramProfile(): ?string
    {
        return $this->instagramProfile;
    }

    public function setInstagramProfile(?string $instagramProfile): static
    {
        $this->instagramProfile = $instagramProfile;

        return $this;
    }

    public function getFacebookProfile(): ?string
    {
        return $this->facebookProfile;
    }

    public function setFacebookProfile(?string $facebookProfile): static
    {
        $this->facebookProfile = $facebookProfile;

        return $this;
    }

    public function getYoutubeProfile(): ?string
    {
        return $this->youtubeProfile;
    }

    public function setYoutubeProfile(?string $youtubeProfile): static
    {
        $this->youtubeProfile = $youtubeProfile;

        return $this;
    }
}
