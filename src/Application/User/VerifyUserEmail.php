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
        if (null === $user) {
            throw new InvalidEmailVerificationTokenException('Invalid or expired verification token.');
        }

        $userId = $user->getId();
        if (null === $userId) {
            throw new UserIdentifierMissingException('#ID cannot be null.');
        }

        $this->userRepository->activate($userId);
    }
}
