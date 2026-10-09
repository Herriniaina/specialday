<?php

namespace App\Infrastructure\Shared\Http\EventListeners;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders every error of an /api route in the API envelope ({statusCode, statusText}) instead of
 * Symfony's HTML error page: unknown route or company, method not allowed, access denied…
 * Runs after ValidationExceptionListener and the firewall's exception listener (which answer
 * validation errors and authentication with their own envelope), and before Symfony's
 * ErrorListener (-128); exceptions are still logged by ErrorListener::logKernelException.
 * Unexpected errors keep their details out of the response.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();
        if ('/api' !== $path && !str_starts_with($path, '/api/')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof HttpExceptionInterface) {
            $statusCode = $throwable->getStatusCode();
            $statusText = '' !== $throwable->getMessage() ? $throwable->getMessage() : (Response::$statusTexts[$statusCode] ?? 'Error');
            $headers = $throwable->getHeaders();
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $statusText = 'Internal server error';
            $headers = [];
        }

        $event->setResponse(new JsonResponse(['statusCode' => $statusCode, 'statusText' => $statusText], $statusCode, $headers));
    }
}
