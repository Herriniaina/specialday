<?php

namespace App\Infrastructure\User\Entity;

use App\Infrastructure\Company\Entity\Company;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\UuidInterface;

/**
 * Links a Keycloak account ($identityProviderId, the "sub" claim of its tokens) to its company.
 * Email, name, phone, credentials and roles are only held by Keycloak.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_user_identity_provider_id', columns: ['identity_provider_id'])]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private ?UuidInterface $id = null;

    // Nullable for the users created before Keycloak, until they are linked to an account.
    #[ORM\Column(name: 'identity_provider_id', length: 36, nullable: true)]
    private ?string $identityProviderId = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: true)]
    private ?Company $company = null;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getIdentityProviderId(): ?string
    {
        return $this->identityProviderId;
    }

    public function setIdentityProviderId(?string $identityProviderId): static
    {
        $this->identityProviderId = $identityProviderId;

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
}
