<?php

namespace App\Infrastructure\Security\Keycloak;

use App\Application\User\AccountRejectedException;
use App\Application\User\EmailAlreadyExistsException;
use App\Application\User\IdentityProviderInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Keycloak Admin REST API, called with the service account of the confidential "specialday-api"
 * client (client credentials grant; realm-management roles granted in the realm import).
 */
final readonly class KeycloakAdminClient implements IdentityProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $internalUrl,
        private string $realm,
        private string $clientId,
        private string $clientSecret,
    ) {
    }

    public function createAccount(string $email, string $password, string $firstName, string $lastName, ?string $phoneNumber, array $roles): string
    {
        $token = $this->serviceAccountToken();

        $response = $this->send('POST', 'users', $token, array_filter([
            'username' => $email,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'enabled' => true,
            // The realm has "verifyEmail" on: the account cannot sign in until the link sent by
            // sendEmailVerification() is followed.
            'emailVerified' => false,
            'credentials' => [['type' => 'password', 'value' => $password, 'temporary' => false]],
            // Declared in the realm user profile, and exposed as the "phone_number" token claim.
            'attributes' => null === $phoneNumber ? null : ['phoneNumber' => [$phoneNumber]],
        ], static fn (mixed $value): bool => null !== $value));

        $status = $this->statusCode($response);
        if (409 === $status) {
            throw new EmailAlreadyExistsException('Email already exist.');
        }
        if (400 === $status) {
            throw new AccountRejectedException($this->errorMessage($response));
        }
        if (201 !== $status) {
            throw new KeycloakRequestFailedException(sprintf('Keycloak user creation failed with HTTP %d.', $status));
        }

        // The id of the new user is only given by the Location header (".../users/{id}").
        $location = $response->getHeaders(false)['location'][0] ?? '';
        $accountId = basename((string) parse_url($location, PHP_URL_PATH));
        if ('' === $accountId) {
            throw new KeycloakRequestFailedException('Keycloak user creation response has no Location header.');
        }

        try {
            $this->assignRealmRoles($token, $accountId, $roles);
        } catch (\Throwable $exception) {
            $this->deleteAccount($accountId);

            throw $exception;
        }

        return $accountId;
    }

    public function sendEmailVerification(string $accountId): void
    {
        $this->expectSuccess(
            $this->send('PUT', sprintf('users/%s/send-verify-email', rawurlencode($accountId)), $this->serviceAccountToken()),
            'verification email',
        );
    }

    public function deleteAccount(string $accountId): void
    {
        $response = $this->send('DELETE', sprintf('users/%s', rawurlencode($accountId)), $this->serviceAccountToken());

        // Already gone: nothing left to clean up.
        if (404 !== $this->statusCode($response)) {
            $this->expectSuccess($response, 'user deletion');
        }
    }

    /**
     * @param string[] $roles
     */
    private function assignRealmRoles(string $token, string $accountId, array $roles): void
    {
        if ([] === $roles) {
            return;
        }

        $representations = [];
        foreach ($roles as $role) {
            $response = $this->send('GET', sprintf('roles/%s', rawurlencode($role)), $token);
            $this->expectSuccess($response, sprintf('realm role "%s" lookup', $role));
            $representations[] = $response->toArray(false);
        }

        $this->expectSuccess(
            $this->send('POST', sprintf('users/%s/role-mappings/realm', rawurlencode($accountId)), $token, $representations),
            'realm roles assignment',
        );
    }

    private function serviceAccountToken(): string
    {
        try {
            $response = $this->httpClient->request('POST', sprintf(
                '%s/realms/%s/protocol/openid-connect/token',
                rtrim($this->internalUrl, '/'),
                rawurlencode($this->realm),
            ), [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            throw new KeycloakRequestFailedException('Keycloak service account authentication failed: '.$exception->getMessage(), previous: $exception);
        }

        if (!is_string($response['access_token'] ?? null)) {
            throw new KeycloakRequestFailedException('Keycloak token response is missing the access token.');
        }

        return $response['access_token'];
    }

    /**
     * @param array<mixed>|null $json
     */
    private function send(string $method, string $path, string $token, ?array $json = null): ResponseInterface
    {
        $options = ['auth_bearer' => $token];
        if (null !== $json) {
            $options['json'] = $json;
        }

        return $this->httpClient->request($method, sprintf(
            '%s/admin/realms/%s/%s',
            rtrim($this->internalUrl, '/'),
            rawurlencode($this->realm),
            $path,
        ), $options);
    }

    private function statusCode(ResponseInterface $response): int
    {
        try {
            return $response->getStatusCode();
        } catch (ExceptionInterface $exception) {
            throw new KeycloakRequestFailedException('Keycloak Admin API is unreachable: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function expectSuccess(ResponseInterface $response, string $operation): void
    {
        $status = $this->statusCode($response);
        if ($status < 200 || $status >= 300) {
            throw new KeycloakRequestFailedException(sprintf('Keycloak %s failed with HTTP %d.', $operation, $status));
        }
    }

    private function errorMessage(ResponseInterface $response): string
    {
        try {
            $body = $response->toArray(false);
        } catch (ExceptionInterface) {
            $body = [];
        }

        $message = $body['errorMessage'] ?? $body['error_description'] ?? $body['error'] ?? null;

        return is_string($message) && '' !== $message ? $message : 'The account was rejected by Keycloak.';
    }
}
