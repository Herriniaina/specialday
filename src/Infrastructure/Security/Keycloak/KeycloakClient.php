<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * OIDC Authorization Code flow (+ PKCE) against the Keycloak realm, for a confidential client.
 *
 * Two base URLs: the browser is redirected to $publicUrl (authorize, logout), while the code is
 * exchanged server-side through $internalUrl (in dev, "keycloak:8080" from the php container).
 */
final readonly class KeycloakClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $publicUrl,
        private string $internalUrl,
        private string $realm,
        private string $clientId,
        private string $clientSecret,
    ) {
    }

    public function createAuthorization(string $redirectUri): KeycloakAuthorization
    {
        $state = bin2hex(random_bytes(16));
        $codeVerifier = self::base64UrlEncode(random_bytes(32));

        $url = $this->endpoint($this->publicUrl, 'auth').'?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => 'openid',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => self::base64UrlEncode(hash('sha256', $codeVerifier, true)),
            'code_challenge_method' => 'S256',
        ]);

        return new KeycloakAuthorization($url, $state, $codeVerifier);
    }

    /**
     * The tokens come straight from the token endpoint, authenticated with the client secret: per
     * OIDC Core 3.1.3.7 that channel vouches for them, so their signature is not re-verified here.
     *
     * @throws KeycloakRequestFailedException
     */
    public function exchangeCode(string $code, string $redirectUri, string $codeVerifier): KeycloakTokens
    {
        try {
            $response = $this->httpClient->request('POST', $this->endpoint($this->internalUrl, 'token'), [
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'code_verifier' => $codeVerifier,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            throw new KeycloakRequestFailedException('Keycloak code exchange failed: '.$exception->getMessage(), previous: $exception);
        }

        if (!is_string($response['access_token'] ?? null) || !is_string($response['id_token'] ?? null)) {
            throw new KeycloakRequestFailedException('Keycloak token response is missing the access or ID token.');
        }

        return new KeycloakTokens(self::decodeJwtPayload($response['access_token']), $response['id_token']);
    }

    public function logoutUrl(string $idToken, string $postLogoutRedirectUri): string
    {
        return $this->endpoint($this->publicUrl, 'logout').'?'.http_build_query([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ]);
    }

    private function endpoint(string $baseUrl, string $name): string
    {
        return sprintf('%s/realms/%s/protocol/openid-connect/%s', rtrim($baseUrl, '/'), rawurlencode($this->realm), $name);
    }

    /**
     * @return array<mixed>
     */
    private static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $payload = 3 === count($parts) ? base64_decode(strtr($parts[1], '-_', '+/'), true) : false;
        $claims = false !== $payload ? json_decode($payload, true) : null;

        if (!is_array($claims)) {
            throw new KeycloakRequestFailedException('Keycloak access token is not a readable JWT.');
        }

        return $claims;
    }

    private static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
