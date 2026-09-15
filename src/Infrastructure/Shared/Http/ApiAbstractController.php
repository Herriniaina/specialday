<?php

namespace App\Infrastructure\Shared\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiAbstractController
{
    private function jsonResponse(int $statusCode, string $statusText, mixed $payload = null): JsonResponse
    {
        $response = [
            'statusCode' => $statusCode,
            'statusText' => $statusText,
        ];

        if (null !== $payload) {
            $response['payload'] = $payload;
        }

        return new JsonResponse($response, $statusCode);
    }

    protected function success(mixed $payload = null, string $statusText = 'Success'): JsonResponse
    {
        return $this->jsonResponse(Response::HTTP_OK, $statusText, $payload);
    }

    protected function created(mixed $payload = null, string $statusText = 'Created'): JsonResponse
    {
        return $this->jsonResponse(Response::HTTP_CREATED, $statusText, $payload);
    }

    /**
     * @param mixed[] $payload
     */
    protected function badRequest(string $statusText = 'Invalid parameter', array $payload = []): JsonResponse
    {
        return $this->jsonResponse(Response::HTTP_BAD_REQUEST, $statusText, $payload);
    }

    protected function notFound(string $statusText = 'Resource not found.'): JsonResponse
    {
        return $this->jsonResponse(Response::HTTP_NOT_FOUND, $statusText);
    }

    protected function conflict(string $statusText = 'Conflict detected.'): JsonResponse
    {
        return $this->jsonResponse(Response::HTTP_CONFLICT, $statusText);
    }

    protected function error(string $statusText = 'Internal server error'): JsonResponse
    {
        return $this->jsonResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $statusText);
    }
}
