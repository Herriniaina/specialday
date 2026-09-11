<?php

namespace App\Infrastructure\Shared\Http\EventListeners;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Maps the exceptions thrown by #[MapRequestPayload] onto the API error
 * envelope ({statusCode, statusText, payload}) used across the application.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $previous = $throwable->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $errors = [];
            foreach ($previous->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            $event->setResponse($this->envelope('Invalid parameter', $errors));

            return;
        }

        if ($previous instanceof SerializerException) {
            $event->setResponse($this->envelope('Invalid json format.'));
        }
    }

    private function envelope(string $statusText, array $payload = []): JsonResponse
    {
        $body = [
            'statusCode' => Response::HTTP_BAD_REQUEST,
            'statusText' => $statusText,
        ];

        if ($payload !== []) {
            $body['payload'] = $payload;
        }

        return new JsonResponse($body, Response::HTTP_BAD_REQUEST);
    }
}
