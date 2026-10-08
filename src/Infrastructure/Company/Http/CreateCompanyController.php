<?php

namespace App\Infrastructure\Company\Http;

use App\Application\Company\CreateCompany;
use App\Application\Company\CreateCompanyRequest;
use App\Application\Company\UserAlreadyHasCompanyException;
use App\Infrastructure\Security\Keycloak\ApiUser;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CreateCompanyController extends ApiAbstractController
{
    public function __construct(
        private CreateCompany $createCompany,
    ) {
    }

    #[IsGranted('ROLE_COMPANY_ADMIN')]
    #[Route('/api/company', name: 'app_company_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateCompanyInput $input,
        #[CurrentUser] ApiUser $user,
    ): JsonResponse {
        try {
            $company = $this->createCompany->execute(
                new CreateCompanyRequest(
                    ownerId: $user->userId,
                    name: $input->name,
                    nif: $input->nif,
                    stat: $input->stat,
                    rcs: $input->rcs,
                    phone: $input->phone,
                    instagramProfile: $input->instagramProfile,
                    facebookProfile: $input->facebookProfile,
                    youtubeProfile: $input->youtubeProfile,
                ),
            );
        } catch (UserAlreadyHasCompanyException $e) {
            return $this->conflict($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created(['id' => $company->getId(), 'slug' => $company->getSlug()]);
    }
}
