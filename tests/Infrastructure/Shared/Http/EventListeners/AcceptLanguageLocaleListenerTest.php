<?php

namespace App\Tests\Infrastructure\Shared\Http\EventListeners;

use App\Infrastructure\Shared\Http\EventListeners\AcceptLanguageLocaleListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AcceptLanguageLocaleListenerTest extends TestCase
{
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->kernel = $this->createStub(HttpKernelInterface::class);
    }

    public function testOnKernelRequestSetsLocaleFromAcceptLanguageHeaderWhenSupported(): void
    {
        $listener = new AcceptLanguageLocaleListener(new RequestStack(), 'en');

        $request = Request::create('/', 'GET', server: [
            'HTTP_ACCEPT_LANGUAGE' => 'es-ES,es;q=0.9,en;q=0.8',
        ]);

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame('es', $request->getLocale());
    }

    public function testOnKernelRequestFallsBackToFirstSupportedLocaleWhenNoneMatch(): void
    {
        $listener = new AcceptLanguageLocaleListener(new RequestStack(), 'en');

        $request = Request::create('/', 'GET', server: [
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9',
        ]);

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame('fr', $request->getLocale());
    }

    public function testOnKernelRequestDoesNotOverrideAnExplicitLocaleAttribute(): void
    {
        $listener = new AcceptLanguageLocaleListener(new RequestStack(), 'en');

        $request = Request::create('/', 'GET', server: [
            'HTTP_ACCEPT_LANGUAGE' => 'es-ES,es;q=0.9',
        ]);
        $request->attributes->set('_locale', 'fr');

        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelRequest($event);

        self::assertSame('fr', $request->getLocale());
    }
}
