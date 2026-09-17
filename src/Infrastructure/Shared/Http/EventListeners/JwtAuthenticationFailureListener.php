<?php

namespace App\Infrastructure\Shared\Http\EventListeners;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;

class JwtAuthenticationFailureListener
{
    public function onJwtNotFound(JWTNotFoundEvent $event): void
    {
        $event->setResponse(new JWTAuthenticationFailureResponse('Unauthorized'));
    }

    public function onJwtInvalid(JWTInvalidEvent $event): void
    {
        $event->setResponse(new JWTAuthenticationFailureResponse('Invalid token'));
    }

    public function onJwtExpired(JWTExpiredEvent $event): void
    {
        $event->setResponse(new JWTAuthenticationFailureResponse('Expired token'));
    }
}
