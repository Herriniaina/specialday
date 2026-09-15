<?php

namespace App\Infrastructure\User\Repository;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use App\Infrastructure\User\Entity\User as EntityUser;
use App\Infrastructure\User\Entity\User as UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function save(User $user): void
    {
        $entityUser = new EntityUser();
        $entityUser->setEmail($user->email);
        $entityUser->setFirstName($user->firstName);
        $entityUser->setLastName($user->lastName);
        $entityUser->setPhoneNumber($user->phoneNumber);
        $entityUser->setRoles($user->roles);
        $entityUser->setPassword($user->password);
        $entityUser->setIsActive($user->isActive);
        $entityUser->setEmailVerificationToken($user->emailVerificationToken);

        if (null !== $user->companyId) {
            $entityUser->setCompany($this->em->getReference(CompanyEntity::class, $user->companyId));
        }

        $this->em->persist($entityUser);
        $this->em->flush();
    }

    #[\Override]
    public function existsByEmail(string $email): bool
    {
        return null !== $this->em->getRepository(UserEntity::class)->findOneBy(['email' => $email]);
    }

    #[\Override]
    public function findById(string $id): ?User
    {
        $entity = $this->em->find(UserEntity::class, Uuid::fromString($id));

        return null === $entity ? null : $this->toDomain($entity);
    }

    #[\Override]
    public function findByEmailVerificationToken(string $token): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)
            ->findOneBy(['emailVerificationToken' => $token]);

        return null === $entity ? null : $this->toDomain($entity);
    }

    #[\Override]
    public function activate(string $userId): void
    {
        $entity = $this->em->find(UserEntity::class, Uuid::fromString($userId));
        if (null === $entity) {
            return;
        }

        $entity->setIsActive(true);
        $entity->setEmailVerificationToken(null);
        $this->em->flush();
    }

    private function toDomain(UserEntity $entity): User
    {
        return new User(
            email: (string) $entity->getEmail(),
            password: (string) $entity->getPassword(),
            roles: $entity->getRoles(),
            firstName: $entity->getFirstName(),
            lastName: $entity->getLastName(),
            phoneNumber: $entity->getPhoneNumber(),
            companyId: $entity->getCompany()?->getId(),
            isActive: $entity->isActive(),
            emailVerificationToken: $entity->getEmailVerificationToken(),
            id: (string) $entity->getId(),
        );
    }
}
