<?php

namespace App\Infrastructure\Shared\Http\EventListeners;

use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class AcceptLanguageLocaleListener extends LocaleListener
{
    private array $supportedLocales = ['fr', 'en', 'es']; // À adapter ou injecter

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->attributes->get('_locale')) {
            $preferredLanguage = $request->getPreferredLanguage($this->supportedLocales);
            
            if ($preferredLanguage) {
                $request->setLocale($preferredLanguage);
            }
        }

        parent::onKernelRequest($event);
    }
}
