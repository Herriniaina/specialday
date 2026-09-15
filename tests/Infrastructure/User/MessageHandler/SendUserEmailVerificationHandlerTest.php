<?php

namespace App\Tests\Infrastructure\User\MessageHandler;

use App\Infrastructure\User\Message\SendUserEmailVerification;
use App\Infrastructure\User\MessageHandler\SendUserEmailVerificationHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class SendUserEmailVerificationHandlerTest extends TestCase
{
    public function testInvokeSendsAVerificationEmailWithTheExpectedLink(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('jane@example.com', array_key_first(array_flip(array_map(
                    static fn ($address) => $address->getAddress(),
                    $email->getTo(),
                ))));
                self::assertSame('no-reply@specialday.test', $email->getFrom()[0]->getAddress());
                self::assertSame(
                    'https://app.specialday.test/verify-email?token=abc%2Btoken',
                    $email->getContext()['verificationLink'],
                );
                self::assertSame('Jane', $email->getContext()['firstName']);

                return true;
            }));

        $handler = new SendUserEmailVerificationHandler(
            $mailer,
            'https://app.specialday.test/',
            'no-reply@specialday.test',
        );

        $handler(new SendUserEmailVerification('jane@example.com', 'Jane', 'abc+token'));
    }
}
