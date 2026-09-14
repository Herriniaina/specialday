<?php

namespace App\Application\User;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;

class RegisterUser {
    public function __construct(
        public UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserNotifierInterface $notifier,
    ) {}

    public function execute(
        RegisterUserRequest $registerUserRequest
    ): void {
        if ($this->userRepository->existsByEmail($registerUserRequest->email)) throw new EmailAlreadyExistsException("Email already exist.");

        $verificationToken = bin2hex(random_bytes(32));

        $user = new User(
            email: $registerUserRequest->email,
            password: $this->passwordHasher->hash($registerUserRequest->password),
            roles: $registerUserRequest->roles,
            firstName: $registerUserRequest->firstName,
            lastName: $registerUserRequest->lastName,
            phoneNumber: $registerUserRequest->phoneNumber,
            companyId: $registerUserRequest->companyId,
            isActive: false,
            emailVerificationToken: $verificationToken,
        );

        $this->userRepository->save($user);
        $this->notifier->emailVerificationRequested($user, $verificationToken);
    }
}
