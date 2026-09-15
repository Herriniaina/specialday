<?php

namespace App\Infrastructure\Shared\Http\EventListeners;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;

class AcceptLanguageLocaleListener extends LocaleListener
{
    /** @var string[] */
    private array $supportedLocales = ['fr', 'en', 'es'];

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
