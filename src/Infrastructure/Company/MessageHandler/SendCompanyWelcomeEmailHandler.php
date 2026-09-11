<?php

namespace App\Infrastructure\Company\MessageHandler;

use App\Infrastructure\Company\Message\SendCompanyWelcomeEmail;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;

#[AsMessageHandler]
final class SendCompanyWelcomeEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $appBaseUrl,
        private string $mailerFrom,
    ) {
    }

    public function __invoke(SendCompanyWelcomeEmail $message): void
    {
        $invitationLink = sprintf(
            '%s/register?token=%s',
            rtrim($this->appBaseUrl, '/'),
            urlencode($message->token),
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFrom, 'SpecialDay'))
            ->to($message->email)
            ->subject(sprintf('Bienvenue sur SpecialDay - %s', $message->companyName))
            ->textTemplate('email/company/welcome.txt.twig')
            ->htmlTemplate('email/company/welcome.html.twig')
            ->context([
                'companyName' => $message->companyName,
                'invitationLink' => $invitationLink,
                'accessCode' => $message->accessCode,
            ]);

        $this->mailer->send($email);
    }
}
