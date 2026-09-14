<?php

namespace App\Infrastructure\Company\Repository;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use Doctrine\ORM\EntityManagerInterface;
use Override;

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
            ->setSlug($company->slug)
            ->setCreatedAt($company->createdAt)
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
    public function existsBySlug(string $slug): bool
    {
        return $this->em->getRepository(CompanyEntity::class)
            ->findOneBy([
                'slug' => $slug
            ]) !== null;
    }
}
