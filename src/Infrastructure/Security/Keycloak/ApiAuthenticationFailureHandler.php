<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * JSON 401 of the api firewall, in the ApiAbstractController format: as entry point when no token
 * was sent, as failure handler when the token is rejected (bad signature, expired, wrong audience,
 * no linked user…).
 */
final class ApiAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface, AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return self::unauthorized('Unauthorized', 'Bearer');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return self::unauthorized('Invalid token', 'Bearer error="invalid_token"');
    }

    private static function unauthorized(string $statusText, string $challenge): JsonResponse
    {
        return new JsonResponse(
            ['statusCode' => Response::HTTP_UNAUTHORIZED, 'statusText' => $statusText],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => $challenge],
        );
    }
}
