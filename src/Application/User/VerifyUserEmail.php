<?php

namespace App\Application\User;

use App\Domain\User\UserRepositoryInterface;

class VerifyUserEmail
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(VerifyUserEmailRequest $request): void
    {
        $user = $this->userRepository->findByEmailVerificationToken($request->token);
        if ($user === null) {
            throw new InvalidEmailVerificationTokenException('Invalid or expired verification token.');
        }

        $this->userRepository->activate($user->getId());
    }
}
