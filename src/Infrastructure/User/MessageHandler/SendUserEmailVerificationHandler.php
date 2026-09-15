<?php

namespace App\Infrastructure\User\MessageHandler;

use App\Infrastructure\User\Message\SendUserEmailVerification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final class SendUserEmailVerificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $appBaseUrl,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(SendUserEmailVerification $message): void
    {
        $verificationLink = sprintf(
            '%s/verify-email?token=%s',
            rtrim($this->appBaseUrl, '/'),
            urlencode($message->token),
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'SpecialDay'))
            ->to($message->email)
            ->subject('Vérifiez votre adresse email - SpecialDay')
            ->textTemplate('email/user/verify_email.txt.twig')
            ->htmlTemplate('email/user/verify_email.html.twig')
            ->context([
                'firstName' => $message->firstName,
                'verificationLink' => $verificationLink,
            ]);

        $this->mailer->send($email);
    }
}
