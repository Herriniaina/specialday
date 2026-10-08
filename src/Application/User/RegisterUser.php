<?php

namespace App\Application\User;

use App\Application\Shared\TransactionInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;

class RegisterUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private IdentityProviderInterface $identityProvider,
        private TransactionInterface $transaction,
    ) {
    }

    public function execute(
        RegisterUserRequest $registerUserRequest,
    ): void {
        // Throws EmailAlreadyExistsException: Keycloak enforces the email uniqueness.
        $accountId = $this->identityProvider->createAccount(
            $registerUserRequest->email,
            $registerUserRequest->password,
            $registerUserRequest->firstName,
            $registerUserRequest->lastName,
            '' === $registerUserRequest->phoneNumber ? null : $registerUserRequest->phoneNumber,
            $registerUserRequest->roles,
        );

        try {
            $this->transaction->transactional(function () use ($accountId): void {
                // The company is created afterwards, signed in (see CreateCompany).
                $this->userRepository->save(new User(identityProviderId: $accountId));
                $this->identityProvider->sendEmailVerification($accountId);
            });
        } catch (\Throwable $exception) {
            // No account without its local profile: the registration can simply be retried.
            $this->identityProvider->deleteAccount($accountId);

            throw $exception;
        }
    }
}
