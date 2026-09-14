<?php

namespace App\Application\Company;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\SlugGeneratorInterface;

class CreateCompany
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
        private IdGeneratorInterface $idGenerator,
        private SlugGeneratorInterface $slugGenerator,
    ) {
    }

    public function execute(CreateCompanyRequest $request): Company
    {
        $company = new Company(
            id: $this->idGenerator->generate(),
            name: $request->name,
            slug: $this->generateUniqueSlug($request->name),
            createdAt: new \DateTimeImmutable(),
            nif: $request->nif,
            stat: $request->stat,
            rcs: $request->rcs,
            phone: $request->phone,
            instagramProfile: $request->instagramProfile,
            facebookProfile: $request->facebookProfile,
            youtubeProfile: $request->youtubeProfile,
        );

        $this->companyRepository->save($company);

        return $company;
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugGenerator->slugify($name);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->companyRepository->existsBySlug($slug)) {
            $slug = \sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        }

        return $slug;
    }
}
