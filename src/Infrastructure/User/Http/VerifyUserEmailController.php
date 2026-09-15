<?php

namespace App\Infrastructure\User\Http;

use App\Application\User\InvalidEmailVerificationTokenException;
use App\Application\User\VerifyUserEmail;
use App\Application\User\VerifyUserEmailRequest;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class VerifyUserEmailController extends ApiAbstractController
{
    public function __construct(
        private VerifyUserEmail $verifyUserEmail,
    ) {
    }

    #[Route('/api/user/verify-email/{token}', name: 'app_user_verify_email', methods: ['POST'])]
    public function __invoke(string $token): JsonResponse
    {
        try {
            $this->verifyUserEmail->execute(new VerifyUserEmailRequest($token));
        } catch (InvalidEmailVerificationTokenException $e) {
            return $this->badRequest($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->success();
    }
}
