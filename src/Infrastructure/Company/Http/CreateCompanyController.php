<?php

namespace App\Infrastructure\Company\Http;

use App\Application\Company\CreateCompany;
use App\Application\Company\CreateCompanyRequest;
use App\Domain\Company\CompanyEmailAlreadyExistsException;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateCompanyController extends ApiAbstractController
{
    public function __construct(
        private CreateCompany $createCompany,
    ) {
    }

    #[Route('/api/company', name: 'app_company_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateCompanyInput $input): JsonResponse
    {
        try {
            $this->createCompany->execute(
                new CreateCompanyRequest(
                    name: $input->name,
                    email: $input->email,
                    userId: $input->userId,
                    nif: $input->nif,
                    stat: $input->stat,
                    rcs: $input->rcs,
                    phone: $input->phone,
                    instagramProfile: $input->instagramProfile,
                    facebookProfile: $input->facebookProfile,
                    youtubeProfile: $input->youtubeProfile,
                ),
            );
        } catch (CompanyEmailAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (Exception) {
            return $this->error();
        }

        return $this->created();
    }
}
