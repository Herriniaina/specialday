<?php

namespace App\Infrastructure\User\Repository;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\User\Entity\User as EntityUser;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use App\Infrastructure\User\Entity\User as UserEntity;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Override]
    public function save(User $user): void
    {
        $entityUser = new EntityUser();
        $entityUser->setEmail($user?->email);
        $entityUser->setFirstName($user->firstName);
        $entityUser->setLastName($user->lastName);
        $entityUser->setPhoneNumber($user->phoneNumber);
        $entityUser->setRoles($user->roles);
        $entityUser->setPassword($user->password);

        $this->em->persist($entityUser);
        $this->em->flush();
    }

    #[Override]
    public function existsByEmail(string $email): bool
    {
        return $this->em->getRepository(UserEntity::class)->findOneBy(['email' => $email]) !== null;
    }
}
