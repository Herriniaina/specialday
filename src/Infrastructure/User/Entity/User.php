<?php

namespace App\Infrastructure\User\Entity;

use App\Domain\User\UserRoleEnum;
use App\Infrastructure\Company\Entity\Company;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
class User  implements PasswordAuthenticatedUserInterface, UserInterface
{
    public const ROLE_USER = 'ROLE_USER';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'user.email.not_blank')]
    #[Assert\Email(message: 'user.email.invalid')]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\+?[1-9]\d{1,14}$/',
        message: 'user.phone.invalid'
    )]
    private ?string $phone_number = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'user.password.not_blank')]
    #[Assert\Length(min: 8, minMessage: 'user.password.too_short')]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    #[Assert\NotNull]
    #[Assert\All([
        new Assert\Type(type: 'string'),
        new Assert\Choice(
            choices: ['ROLE_USER', 'ROLE_COMPANY_ADMIN', 'ROLE_ADMIN'],
            message: 'user.roles.invalid'
        ),
    ])]
    private array $roles = [];

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: true)]
    private ?Company $company = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $emailVerificationToken = null;

    public function getId(): ?UuidInterface
    {
        return $this->id;
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

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): static
    {
        $this->emailVerificationToken = $emailVerificationToken;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (empty($roles)) {
            return [self::ROLE_USER];
        }

        return array_unique($roles);
    }

    public function getRole(): string
    {
        return $this->roles[0] ?? self::ROLE_USER;
    }

    public function setRole(string $role): static
    {
        return $this->setRoles([$role]);
    }

    public function eraseCredentials(): void
    {
    }

    public function setRoles(array $roles): static
    {
        $rolesToCheck = array_diff($roles, [self::ROLE_USER]);

        foreach ($rolesToCheck as $role) {
            if (UserRoleEnum::tryFrom($role) === null) {
                throw new \InvalidArgumentException(sprintf(
                    'Role "%s" is not valid.',
                    $role
                ));
            }
        }

        if ($this->isNewUser() &&
        !in_array(UserRoleEnum::COMPANY_ADMIN->value,$rolesToCheck) &&
        !empty($rolesToCheck)) {
            throw new BadRequestException("Role cannot be set.");
        }

        $this->roles = $rolesToCheck;

        return $this;
    }

    private function isNewUser(): bool
    {
        return $this->id === null;
    }
}
