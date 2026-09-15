<?php

namespace App\Infrastructure\User\Notifier;

use App\Application\User\UserNotifierInterface;
use App\Domain\User\User;
use App\Infrastructure\User\Message\SendUserEmailVerification;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerUserNotifier implements UserNotifierInterface
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    #[\Override]
    public function emailVerificationRequested(User $user, string $verificationToken): void
    {
        $this->bus->dispatch(new SendUserEmailVerification(
            email: $user->email,
            firstName: $user->firstName,
            token: $verificationToken,
        ));
    }
}
