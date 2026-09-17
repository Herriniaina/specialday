<?php

namespace App\Infrastructure\Media\Repository;

use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use App\Infrastructure\Media\Entity\MediaFile as MediaFileEntity;
use App\Infrastructure\User\Entity\User as UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class MediaFileRepository implements MediaFileRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function save(MediaFile $mediaFile): void
    {
        $entity = (null !== $mediaFile->id ? $this->em->find(MediaFileEntity::class, $mediaFile->id) : null) ?? new MediaFileEntity();

        $company = $this->em->getReference(CompanyEntity::class, $mediaFile->companyId);
        \assert($company instanceof CompanyEntity);

        $createdBy = $this->em->getReference(UserEntity::class, Uuid::fromString($mediaFile->createdBy));
        \assert($createdBy instanceof UserEntity);

        $entity
            ->setId($mediaFile->id ?? (string) $entity->getId())
            ->setCompany($company)
            ->setFilename($mediaFile->filename)
            ->setFileSize($mediaFile->fileSize)
            ->setFileType($mediaFile->fileType)
            ->setStorage($mediaFile->storage->value)
            ->setPath($mediaFile->path)
            ->setCreatedBy($createdBy)
            ->setCreatedAt($mediaFile->createdAt)
            ->setUpdatedAt($mediaFile->updatedAt)
            ->setDeletedAt($mediaFile->deletedAt);

        if (null !== $mediaFile->updatedBy) {
            $updatedBy = $this->em->getReference(UserEntity::class, Uuid::fromString($mediaFile->updatedBy));
            \assert($updatedBy instanceof UserEntity);
            $entity->setUpdatedBy($updatedBy);
        }

        if (null !== $mediaFile->deletedBy) {
            $deletedBy = $this->em->getReference(UserEntity::class, Uuid::fromString($mediaFile->deletedBy));
            \assert($deletedBy instanceof UserEntity);
            $entity->setDeletedBy($deletedBy);
        }

        $this->em->persist($entity);
        $this->em->flush();
    }
}
