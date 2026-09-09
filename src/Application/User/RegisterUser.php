<?php

namespace App\Application\User;

use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use App\Application\User\PasswordHasherInterface;

class RegisterUser {
    public function __construct(
        public UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function execute(
        RegisterUserRequest $registerUserRequest
    ): void {
        if ($this->userRepository->existsByEmail($registerUserRequest->email)) throw new EmailAlreadyExistsException("Email already exist.");

        $user = new User(
            $registerUserRequest->email,
            $registerUserRequest->password,
            $registerUserRequest->roles,
            $registerUserRequest->firstName,
            $registerUserRequest->lastName,
            $registerUserRequest->phoneNumber
        );

        $user->password = $this->passwordHasher->hash($user->password);
        $this->userRepository->save($user);
    }
}
