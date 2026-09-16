<?php

namespace App\Infrastructure\User\Http;

use App\Application\User\ChangePassword;
use App\Application\User\ChangePasswordRequest;
use App\Application\User\InvalidCurrentPasswordException;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\User\Entity\User as UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChangePasswordController extends ApiAbstractController
{
    public function __construct(
        private ChangePassword $changePassword,
    ) {
    }

    #[Route('/api/user/change-password', name: 'app_user_change_password', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] ChangePasswordInput $input,
        #[CurrentUser] UserEntity $user,
    ): JsonResponse {
        try {
            $this->changePassword->execute(new ChangePasswordRequest(
                userId: (string) $user->getId(),
                currentPassword: $input->currentPassword,
                newPassword: $input->newPassword,
            ));
        } catch (InvalidCurrentPasswordException $e) {
            return $this->badRequest($e->getMessage());
        } catch (UserNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->success();
    }
}
