<?php

namespace App\Application\Company;

use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\SlugGeneratorInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;

class CreateCompany
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
        private IdGeneratorInterface $idGenerator,
        private SlugGeneratorInterface $slugGenerator,
        private UserRepositoryInterface $userRepository,
        private TransactionInterface $transaction,
    ) {
    }

    /**
     * The owner is linked to the company it creates: a user can own a single company, and can
     * never be attached to someone else's company from here.
     *
     * @throws UserAlreadyHasCompanyException
     */
    public function execute(CreateCompanyRequest $request): Company
    {
        if (null !== $this->userRepository->findById($request->ownerId)?->getCompanyId()) {
            throw new UserAlreadyHasCompanyException('User already has a company.');
        }

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

        $this->transaction->transactional(function () use ($company, $request): void {
            $this->companyRepository->save($company);

            // Checked again atomically: a concurrent request may have attached a company meanwhile.
            if (!$this->userRepository->attachToCompany($request->ownerId, $company->getId())) {
                throw new UserAlreadyHasCompanyException('User already has a company.');
            }
        });

        return $company;
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugGenerator->slugify($name);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->companyRepository->existsBySlug($slug)) {
            $slug = \sprintf('%s-%d', $baseSlug, $suffix);
            ++$suffix;
        }

        return $slug;
    }
}
