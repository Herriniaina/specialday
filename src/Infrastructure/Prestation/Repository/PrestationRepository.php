<?php

namespace App\Infrastructure\Prestation\Repository;

use App\Domain\Prestation\Prestation;
use App\Domain\Prestation\PrestationRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use App\Infrastructure\Prestation\Entity\Prestation as PrestationEntity;
use App\Infrastructure\PrestationCategory\Entity\PrestationCategory as PrestationCategoryEntity;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

class PrestationRepository implements PrestationRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function save(Prestation $prestation): void
    {
        $entity = (null !== $prestation->id ? $this->em->find(PrestationEntity::class, $prestation->id) : null) ?? new PrestationEntity();

        $company = $this->em->getReference(CompanyEntity::class, $prestation->companyId);
        \assert($company instanceof CompanyEntity);

        $prestationCategory = $this->em->getReference(PrestationCategoryEntity::class, Uuid::fromString($prestation->prestationCategoryId));
        \assert($prestationCategory instanceof PrestationCategoryEntity);

        $entity
            ->setId($prestation->id ?? (string) $entity->getId())
            ->setCompany($company)
            ->setPrestationCategory($prestationCategory)
            ->setPriceAmount($prestation->price->amount)
            ->setPriceCurrency($prestation->price->currency)
            ->setPriceTaxIncluded($prestation->price->taxIncluded)
            ->setQuantity($prestation->quantity)
            ->setUnit($prestation->unit)
            ->setNotes($prestation->notes)
            ->setCreatedAt($prestation->createdAt);

        $this->em->persist($entity);
        $this->em->flush();
    }
}
