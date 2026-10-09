<?php

namespace App\Infrastructure\Prestation\Http;

use App\Application\Prestation\CreatePrestation;
use App\Application\Prestation\CreatePrestationRequest;
use App\Domain\Company\Company;
use App\Domain\PrestationCategory\PrestationCategoryNotFoundException;
use App\Infrastructure\Company\Http\CurrentCompany;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CreatePrestationController extends ApiAbstractController
{
    public function __construct(
        private CreatePrestation $createPrestation,
    ) {
    }

    #[IsGranted('ROLE_COMPANY_ADMIN')]
    #[Route('/api/prestation', name: 'app_prestation_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreatePrestationInput $input,
        #[CurrentCompany] Company $company,
    ): JsonResponse {
        try {
            $prestation = $this->createPrestation->execute(new CreatePrestationRequest(
                companyId: $company->getId(),
                prestationCategoryId: $input->prestationCategoryId,
                amount: (int) $input->price->amount,
                currency: $input->price->currency,
                taxIncluded: (bool) $input->price->taxIncluded,
                quantity: (int) $input->quantity,
                unit: $input->unit,
                notes: $input->notes,
            ));
        } catch (PrestationCategoryNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created(['id' => $prestation->getId()]);
    }
}
