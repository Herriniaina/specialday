<?php

namespace App\Infrastructure\User\Repository;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
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
        $entityUser = new UserEntity();
        $entityUser->setIdentityProviderId($user->identityProviderId);

        if (null !== $user->companyId) {
            $entityUser->setCompany($this->em->getReference(CompanyEntity::class, $user->companyId));
        }

        $this->em->persist($entityUser);
        $this->em->flush();
    }

    #[\Override]
    public function findById(string $id): ?User
    {
        $entity = Uuid::isValid($id) ? $this->em->find(UserEntity::class, Uuid::fromString($id)) : null;

        return null === $entity ? null : $this->toDomain($entity);
    }

    #[\Override]
    public function findByIdentityProviderId(string $identityProviderId): ?User
    {
        $entity = $this->em->getRepository(UserEntity::class)
            ->findOneBy(['identityProviderId' => $identityProviderId]);

        return null === $entity ? null : $this->toDomain($entity);
    }

    #[\Override]
    public function attachToCompany(string $userId, string $companyId): bool
    {
        // A single conditional UPDATE: two concurrent requests cannot both attach a company.
        $updated = $this->em->createQueryBuilder()
            ->update(UserEntity::class, 'u')
            ->set('u.company', ':company')
            ->where('u.id = :id')
            ->andWhere('u.company IS NULL')
            ->setParameter('company', $companyId)
            ->setParameter('id', Uuid::fromString($userId), 'uuid')
            ->getQuery()
            ->execute();

        return 1 === $updated;
    }

    private function toDomain(UserEntity $entity): User
    {
        return new User(
            identityProviderId: (string) $entity->getIdentityProviderId(),
            companyId: $entity->getCompany()?->getId(),
            id: (string) $entity->getId(),
        );
    }
}
