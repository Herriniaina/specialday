<?php

namespace App\Infrastructure\Prestation\Http;

use App\Application\Prestation\CreatePrestation;
use App\Application\Prestation\CreatePrestationRequest;
use App\Domain\PrestationCategory\PrestationCategoryNotFoundException;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\User\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CreatePrestationController extends ApiAbstractController
{
    public function __construct(
        private CreatePrestation $createPrestation,
    ) {
    }

    #[IsGranted('ROLE_COMPANY_ADMIN')]
    #[Route('/api/company/prestation', name: 'app_company_prestation_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreatePrestationInput $input,
        #[CurrentUser] UserEntity $user,
    ): JsonResponse {
        $company = $user->getCompany();
        if (null === $company) {
            return $this->forbidden('prestation.company.required');
        }

        try {
            $prestation = $this->createPrestation->execute(new CreatePrestationRequest(
                companyId: (string) $company->getId(),
                prestationCategoryId: $input->prestationCategoryId,
                amount: (int) $input->price->amount,
                currency: $input->price->currency,
                taxIncluded: (bool) $input->price->taxIncluded,
                quantity: (int) $input->quantity,
                unit: $input->unit,
            ));
        } catch (PrestationCategoryNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created(['id' => $prestation->getId()]);
    }
}
