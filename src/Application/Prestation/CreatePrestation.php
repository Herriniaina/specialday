<?php

namespace App\Application\Prestation;

use App\Application\Shared\IdGeneratorInterface;
use App\Domain\Prestation\Prestation;
use App\Domain\Prestation\PrestationRepositoryInterface;
use App\Domain\PrestationCategory\PrestationCategoryNotFoundException;
use App\Domain\PrestationCategory\PrestationCategoryRepositoryInterface;
use App\Domain\Shared\Price;

class CreatePrestation
{
    public function __construct(
        private PrestationRepositoryInterface $prestationRepository,
        private PrestationCategoryRepositoryInterface $prestationCategoryRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function execute(CreatePrestationRequest $request): Prestation
    {
        if (!$this->prestationCategoryRepository->existsById($request->prestationCategoryId)) {
            throw new PrestationCategoryNotFoundException('Prestation category not found.');
        }

        $prestation = new Prestation(
            companyId: $request->companyId,
            prestationCategoryId: $request->prestationCategoryId,
            price: new Price($request->amount, $request->currency, $request->taxIncluded),
            quantity: $request->quantity,
            unit: $request->unit,
            createdAt: new \DateTimeImmutable(),
            notes: $request->notes,
            id: $this->idGenerator->generate(),
        );

        $this->prestationRepository->save($prestation);

        return $prestation;
    }
}
