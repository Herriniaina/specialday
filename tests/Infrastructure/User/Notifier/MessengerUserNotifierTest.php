<?php

namespace App\Tests\Infrastructure\User\Notifier;

use App\Domain\User\User;
use App\Infrastructure\User\Message\SendUserEmailVerification;
use App\Infrastructure\User\Notifier\MessengerUserNotifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerUserNotifierTest extends TestCase
{
    public function testEmailVerificationRequestedDispatchesAMessageWithUserDataAndToken(): void
    {
        $user = new User(
            email: 'jane@example.com',
            password: 'hashed-password',
            roles: [],
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: null,
        );

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (SendUserEmailVerification $message): bool {
                self::assertSame('jane@example.com', $message->email);
                self::assertSame('Jane', $message->firstName);
                self::assertSame('verification-token', $message->token);

                return true;
            }))
            ->willReturn(new Envelope(new SendUserEmailVerification('jane@example.com', 'Jane', 'verification-token')));

        $notifier = new MessengerUserNotifier($bus);

        $notifier->emailVerificationRequested($user, 'verification-token');
    }
}
