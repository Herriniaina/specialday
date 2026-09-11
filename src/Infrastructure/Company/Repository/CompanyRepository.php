<?php

namespace App\Infrastructure\Company\Repository;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use App\Infrastructure\User\Entity\User as UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Ramsey\Uuid\Uuid;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Override]
    public function save(Company $company): void
    {
        $entity = ($this->em->find(CompanyEntity::class, $company->id) ?? new CompanyEntity())
            ->setId($company->id)
            ->setName($company->name)
            ->setEmail($company->email)
            ->setCreatedAt($company->createdAt)
            ->setAccessCode($company->accessCode)
            ->setToken($company->token)
            ->setUser($this->em->getReference(UserEntity::class, Uuid::fromString($company->userId)))
            ->setNif($company->nif)
            ->setStat($company->stat)
            ->setRcs($company->rcs)
            ->setPhone($company->phone)
            ->setInstagramProfile($company->instagramProfile)
            ->setFacebookProfile($company->facebookProfile)
            ->setYoutubeProfile($company->youtubeProfile);

        $this->em->persist($entity);
        $this->em->flush();
    }

    #[Override]
    public function existsByEmail(string $email): bool
    {
        return $this->em->getRepository(CompanyEntity::class)
            ->findOneBy([
                'email' => $email
            ]) !== null;
    }

    #[Override]
    public function findByToken(string $token): ?Company
    {
        $entity = $this->em->getRepository(CompanyEntity::class)
            ->findOneBy(['token' => $token]);

        return $entity === null ? null : $this->toDomain($entity);
    }

    private function toDomain(CompanyEntity $entity): Company
    {
        return new Company(
            id: (string) $entity->getId(),
            name: (string) $entity->getName(),
            email: (string) $entity->getEmail(),
            createdAt: $entity->getCreatedAt(),
            accessCode: (string) $entity->getAccessCode(),
            token: (string) $entity->getToken(),
            userId: (string) $entity->getUser()?->getId(),
            nif: $entity->getNif(),
            stat: $entity->getStat(),
            rcs: $entity->getRcs(),
            phone: $entity->getPhone(),
            instagramProfile: $entity->getInstagramProfile(),
            facebookProfile: $entity->getFacebookProfile(),
            youtubeProfile: $entity->getYoutubeProfile(),
        );
    }
}
