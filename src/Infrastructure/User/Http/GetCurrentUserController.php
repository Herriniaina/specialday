<?php

namespace App\Infrastructure\User\Http;

use App\Infrastructure\Security\Keycloak\ApiUser;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Profile of the signed-in user, straight from its access token (Keycloak holds the profile) and
 * its local link. A null "company_id" tells the front the company still has to be created
 * (POST /api/company).
 */
final class GetCurrentUserController extends ApiAbstractController
{
    #[Route('/api/user/me', name: 'app_user_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ApiUser $user): JsonResponse
    {
        return $this->success([
            'id' => $user->userId,
            'email' => $user->email,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
            'phone_number' => $user->phoneNumber,
            'company_id' => $user->companyId,
            'roles' => $user->getRoles(),
        ]);
    }
}
