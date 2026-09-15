<?php

namespace App\Infrastructure\User\Http;

use App\Application\User\EmailAlreadyExistsException;
use App\Application\User\RegisterUser;
use App\Application\User\RegisterUserRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterUserController extends ApiAbstractController
{
    public function __construct(
        private RegisterUser $registerUser,
    ) {
    }

    #[Route('/api/user/register', name: 'app_user_register', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] RegisterUserInput $input): JsonResponse
    {
        try {
            $this->registerUser->execute(new RegisterUserRequest(
                email: $input->email,
                password: $input->password,
                roles: $input->roles,
                firstName: $input->firstName,
                lastName: $input->lastName,
                phoneNumber: $input->phoneNumber,
                companyId: $input->companyId,
            ));
        } catch (EmailAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created();
    }
}
