<?php

namespace App\Tests\Infrastructure\Security\Keycloak;

use App\Infrastructure\Security\Keycloak\ApiAuthenticationFailureHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiAuthenticationFailureHandlerTest extends TestCase
{
    public function testMissingTokenIsAJsonUnauthorized(): void
    {
        $response = (new ApiAuthenticationFailureHandler())->start(new Request());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Bearer', $response->headers->get('WWW-Authenticate'));
        self::assertSame(['statusCode' => 401, 'statusText' => 'Unauthorized'], json_decode((string) $response->getContent(), true));
    }

    public function testRejectedTokenIsAJsonUnauthorized(): void
    {
        $response = (new ApiAuthenticationFailureHandler())->onAuthenticationFailure(new Request(), new BadCredentialsException());

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Bearer error="invalid_token"', $response->headers->get('WWW-Authenticate'));
        self::assertSame(['statusCode' => 401, 'statusText' => 'Invalid token'], json_decode((string) $response->getContent(), true));
    }
}
