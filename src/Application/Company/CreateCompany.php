<?php

namespace App\Application\Company;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyEmailAlreadyExistsException;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Application\Shared\IdGeneratorInterface;

class CreateCompany
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
        private CompanyNotifierInterface $notifier,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function execute(CreateCompanyRequest $request): Company
    {
        if ($this->companyRepository->existsByEmail($request->email)) {
            throw new CompanyEmailAlreadyExistsException('Company email already exists.');
        }

        $company = new Company(
            id: $this->idGenerator->generate(),
            name: $request->name,
            email: $request->email,
            createdAt: new \DateTimeImmutable(),
            accessCode: $this->generateAccessCode(),
            token: bin2hex(random_bytes(32)),
            userId: $request->userId,
            nif: $request->nif,
            stat: $request->stat,
            rcs: $request->rcs,
            phone: $request->phone,
            instagramProfile: $request->instagramProfile,
            facebookProfile: $request->facebookProfile,
            youtubeProfile: $request->youtubeProfile,
        );

        $this->companyRepository->save($company);
        $this->notifier->companyRegistered($company);

        return $company;
    }

    private function generateAccessCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, \strlen($alphabet) - 1)];
        }

        return $code;
    }
}
