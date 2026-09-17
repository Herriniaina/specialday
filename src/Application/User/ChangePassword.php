<?php

namespace App\Application\User;

use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepositoryInterface;

class ChangePassword
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function execute(ChangePasswordRequest $request): void
    {
        $user = $this->userRepository->findById($request->userId);
        if (null === $user) {
            throw new UserNotFoundException('User not found.');
        }

        if (!$this->passwordHasher->verify($user->password, $request->currentPassword)) {
            throw new InvalidCurrentPasswordException('Current password is incorrect.');
        }

        $this->userRepository->updatePassword($request->userId, $this->passwordHasher->hash($request->newPassword));
    }
}
