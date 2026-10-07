<?php

namespace App\Tests\Infrastructure\Security\Keycloak;

use App\Infrastructure\Security\Keycloak\KeycloakClient;
use App\Infrastructure\Security\Keycloak\KeycloakRequestFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

class KeycloakClientTest extends TestCase
{
    private const string REDIRECT_URI = 'https://localhost/admin/login/check';

    private function client(MockHttpClient $httpClient = new MockHttpClient()): KeycloakClient
    {
        return new KeycloakClient($httpClient, 'http://localhost:8080/', 'http://keycloak:8080', 'specialday', 'specialday-admin', 'secret');
    }

    /**
     * @param array<string, mixed> $claims
     */
    private static function jwt(array $claims): string
    {
        $encode = static fn (string $json): string => rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        return $encode('{"alg":"RS256"}').'.'.$encode((string) json_encode($claims)).'.signature';
    }

    public function testAuthorizationUrlTargetsThePublicUrlWithPkce(): void
    {
        $authorization = $this->client()->createAuthorization(self::REDIRECT_URI);

        self::assertStringStartsWith('http://localhost:8080/realms/specialday/protocol/openid-connect/auth?', $authorization->url);
        parse_str((string) parse_url($authorization->url, PHP_URL_QUERY), $query);
        self::assertSame('specialday-admin', $query['client_id']);
        self::assertSame('code', $query['response_type']);
        self::assertSame('openid', $query['scope']);
        self::assertSame(self::REDIRECT_URI, $query['redirect_uri']);
        self::assertSame($authorization->state, $query['state']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertSame(
            rtrim(strtr(base64_encode(hash('sha256', $authorization->codeVerifier, true)), '+/', '-_'), '='),
            $query['code_challenge'],
        );
    }

    public function testEachAuthorizationHasItsOwnStateAndVerifier(): void
    {
        $first = $this->client()->createAuthorization(self::REDIRECT_URI);
        $second = $this->client()->createAuthorization(self::REDIRECT_URI);

        self::assertNotSame($first->state, $second->state);
        self::assertNotSame($first->codeVerifier, $second->codeVerifier);
    }

    public function testExchangesTheCodeThroughTheInternalUrl(): void
    {
        $accessToken = self::jwt(['email' => 'admin@specialday.local']);
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($accessToken): JsonMockResponse {
            self::assertSame('POST', $method);
            self::assertSame('http://keycloak:8080/realms/specialday/protocol/openid-connect/token', $url);
            self::assertIsString($options['body']);
            parse_str($options['body'], $body);
            self::assertSame([
                'grant_type' => 'authorization_code',
                'code' => 'the-code',
                'redirect_uri' => self::REDIRECT_URI,
                'code_verifier' => 'the-verifier',
                'client_id' => 'specialday-admin',
                'client_secret' => 'secret',
            ], $body);

            return new JsonMockResponse(['access_token' => $accessToken, 'id_token' => 'the-id-token']);
        });

        $tokens = $this->client($httpClient)->exchangeCode('the-code', self::REDIRECT_URI, 'the-verifier');

        self::assertSame(['email' => 'admin@specialday.local'], $tokens->accessTokenClaims);
        self::assertSame('the-id-token', $tokens->idToken);
    }

    public function testThrowsWhenKeycloakRejectsTheCode(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['error' => 'invalid_grant'], ['http_code' => 400]));

        $this->expectException(KeycloakRequestFailedException::class);

        $this->client($httpClient)->exchangeCode('the-code', self::REDIRECT_URI, 'the-verifier');
    }

    public function testThrowsWhenKeycloakIsUnreachable(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['error' => 'Could not resolve host: keycloak']));

        $this->expectException(KeycloakRequestFailedException::class);

        $this->client($httpClient)->exchangeCode('the-code', self::REDIRECT_URI, 'the-verifier');
    }

    public function testThrowsWhenATokenIsMissing(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['access_token' => self::jwt([])]));

        $this->expectException(KeycloakRequestFailedException::class);

        $this->client($httpClient)->exchangeCode('the-code', self::REDIRECT_URI, 'the-verifier');
    }

    public function testThrowsWhenTheAccessTokenIsNotAJwt(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['access_token' => 'opaque', 'id_token' => 'the-id-token']));

        $this->expectException(KeycloakRequestFailedException::class);

        $this->client($httpClient)->exchangeCode('the-code', self::REDIRECT_URI, 'the-verifier');
    }

    public function testLogoutUrlTargetsThePublicUrl(): void
    {
        $url = $this->client()->logoutUrl('the-id-token', 'https://localhost/admin/login');

        self::assertSame(
            'http://localhost:8080/realms/specialday/protocol/openid-connect/logout?id_token_hint=the-id-token&post_logout_redirect_uri=https%3A%2F%2Flocalhost%2Fadmin%2Flogin',
            $url,
        );
    }
}
