<?php

namespace App\Infrastructure\PrestationCategory\Repository;

use App\Domain\PrestationCategory\PrestationCategoryRepositoryInterface;
use App\Infrastructure\PrestationCategory\Entity\PrestationCategory as PrestationCategoryEntity;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class PrestationCategoryRepository implements PrestationCategoryRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function existsById(string $id): bool
    {
        return null !== $this->em->find(PrestationCategoryEntity::class, Uuid::fromString($id));
    }
}
