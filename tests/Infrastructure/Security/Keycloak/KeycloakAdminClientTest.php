<?php

namespace App\Tests\Infrastructure\Security\Keycloak;

use App\Application\User\AccountRejectedException;
use App\Application\User\EmailAlreadyExistsException;
use App\Infrastructure\Security\Keycloak\KeycloakAdminClient;
use App\Infrastructure\Security\Keycloak\KeycloakRequestFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

class KeycloakAdminClientTest extends TestCase
{
    private const string ADMIN_URL = 'http://keycloak:8080/admin/realms/specialday/';
    private const string ACCOUNT_ID = '0b5f4a52-2f7c-4d1e-9a51-3c2b8f6f1e2a';

    /** @var list<array{method: string, url: string, options: array<string, mixed>}> */
    private array $requests = [];

    /**
     * Replies with $responses in order, after a service account token for each operation.
     *
     * @param list<MockResponse> $responses
     */
    private function client(array $responses): KeycloakAdminClient
    {
        $queue = $responses;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$queue): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            if (str_ends_with($url, '/protocol/openid-connect/token')) {
                return new JsonMockResponse(['access_token' => 'service-token']);
            }

            return array_shift($queue) ?? throw new \LogicException('Unexpected request '.$method.' '.$url);
        });

        return new KeycloakAdminClient($httpClient, 'http://keycloak:8080/', 'specialday', 'specialday-api', 'secret');
    }

    private static function created(): MockResponse
    {
        return new MockResponse('', ['http_code' => 201, 'response_headers' => ['Location' => self::ADMIN_URL.'users/'.self::ACCOUNT_ID]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonBody(int $request): array
    {
        $body = json_decode((string) $this->requests[$request]['options']['body'], true);
        self::assertIsArray($body);

        return $body;
    }

    public function testCreatesTheAccountAndGrantsItsRealmRoles(): void
    {
        $accountId = $this->client([
            self::created(),
            new JsonMockResponse(['id' => 'role-id', 'name' => 'ROLE_COMPANY_ADMIN']),
            new MockResponse('', ['http_code' => 204]),
        ])->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', '+261340000000', ['ROLE_COMPANY_ADMIN']);

        self::assertSame(self::ACCOUNT_ID, $accountId);

        self::assertSame('POST', $this->requests[0]['method']);
        self::assertSame('http://keycloak:8080/realms/specialday/protocol/openid-connect/token', $this->requests[0]['url']);
        self::assertStringContainsString('grant_type=client_credentials', (string) $this->requests[0]['options']['body']);
        self::assertStringContainsString('client_id=specialday-api', (string) $this->requests[0]['options']['body']);

        self::assertSame(['POST', self::ADMIN_URL.'users'], [$this->requests[1]['method'], $this->requests[1]['url']]);
        self::assertContains('Authorization: Bearer service-token', $this->requests[1]['options']['headers']);
        $user = $this->jsonBody(1);
        self::assertSame('jane@example.com', $user['username']);
        self::assertSame('jane@example.com', $user['email']);
        self::assertSame('Jane', $user['firstName']);
        self::assertSame('Doe', $user['lastName']);
        self::assertTrue($user['enabled']);
        self::assertFalse($user['emailVerified']);
        self::assertSame([['type' => 'password', 'value' => 'plain-password', 'temporary' => false]], $user['credentials']);
        self::assertSame(['phoneNumber' => ['+261340000000']], $user['attributes']);

        self::assertSame(['GET', self::ADMIN_URL.'roles/ROLE_COMPANY_ADMIN'], [$this->requests[2]['method'], $this->requests[2]['url']]);
        self::assertSame(['POST', self::ADMIN_URL.'users/'.self::ACCOUNT_ID.'/role-mappings/realm'], [$this->requests[3]['method'], $this->requests[3]['url']]);
        self::assertSame([['id' => 'role-id', 'name' => 'ROLE_COMPANY_ADMIN']], $this->jsonBody(3));
    }

    public function testAnAccountWithoutRolesNorPhoneNeedsNoRoleMappingNorAttribute(): void
    {
        $this->client([self::created()])->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', null, []);

        self::assertCount(2, $this->requests);
        self::assertArrayNotHasKey('attributes', $this->jsonBody(1));
    }

    public function testAnExistingEmailIsAConflict(): void
    {
        $this->expectException(EmailAlreadyExistsException::class);

        $this->client([new JsonMockResponse(['errorMessage' => 'User exists with same email'], ['http_code' => 409])])
            ->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', null, []);
    }

    public function testAnAccountBreakingThePasswordPolicyIsRejected(): void
    {
        $this->expectExceptionObject(new AccountRejectedException('invalidPasswordNotEmailMessage'));

        $this->client([new JsonMockResponse(['errorMessage' => 'invalidPasswordNotEmailMessage'], ['http_code' => 400])])
            ->createAccount('jane@example.com', 'jane@example.com', 'Jane', 'Doe', null, []);
    }

    public function testARejectionWithoutMessageGetsAGenericOne(): void
    {
        $this->expectExceptionObject(new AccountRejectedException('The account was rejected by Keycloak.'));

        $this->client([new MockResponse('not json', ['http_code' => 400])])
            ->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', null, []);
    }

    public function testAnUnexpectedStatusFailsTheCreation(): void
    {
        $this->expectException(KeycloakRequestFailedException::class);

        $this->client([new MockResponse('', ['http_code' => 403])])
            ->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', null, []);
    }

    public function testACreationWithoutLocationFails(): void
    {
        $this->expectException(KeycloakRequestFailedException::class);

        $this->client([new MockResponse('', ['http_code' => 201])])
            ->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', null, []);
    }

    public function testTheAccountIsDeletedWhenItsRolesCannotBeGranted(): void
    {
        $client = $this->client([
            self::created(),
            new MockResponse('', ['http_code' => 404]),
            new MockResponse('', ['http_code' => 204]),
        ]);

        try {
            $client->createAccount('jane@example.com', 'plain-password', 'Jane', 'Doe', null, ['ROLE_UNKNOWN']);
            self::fail('The role lookup failure should be rethrown.');
        } catch (KeycloakRequestFailedException) {
        }

        $last = end($this->requests);
        self::assertSame(['DELETE', self::ADMIN_URL.'users/'.self::ACCOUNT_ID], [$last['method'], $last['url']]);
    }

    public function testSendsTheVerificationEmail(): void
    {
        $this->client([new MockResponse('', ['http_code' => 204])])->sendEmailVerification(self::ACCOUNT_ID);

        self::assertSame(['PUT', self::ADMIN_URL.'users/'.self::ACCOUNT_ID.'/send-verify-email'], [$this->requests[1]['method'], $this->requests[1]['url']]);
    }

    public function testAFailedVerificationEmailIsReported(): void
    {
        $this->expectException(KeycloakRequestFailedException::class);

        $this->client([new MockResponse('', ['http_code' => 500])])->sendEmailVerification(self::ACCOUNT_ID);
    }

    public function testDeletesTheAccount(): void
    {
        $this->client([new MockResponse('', ['http_code' => 204])])->deleteAccount(self::ACCOUNT_ID);

        self::assertSame(['DELETE', self::ADMIN_URL.'users/'.self::ACCOUNT_ID], [$this->requests[1]['method'], $this->requests[1]['url']]);
    }

    public function testDeletingAMissingAccountIsANoOp(): void
    {
        $this->client([new MockResponse('', ['http_code' => 404])])->deleteAccount(self::ACCOUNT_ID);

        self::assertCount(2, $this->requests);
    }

    public function testAFailedDeletionIsReported(): void
    {
        $this->expectException(KeycloakRequestFailedException::class);

        $this->client([new MockResponse('', ['http_code' => 500])])->deleteAccount(self::ACCOUNT_ID);
    }

    public function testAnUnreachableKeycloakIsReported(): void
    {
        $this->expectException(KeycloakRequestFailedException::class);

        $this->client([new MockResponse('', ['error' => 'Connection refused'])])->deleteAccount(self::ACCOUNT_ID);
    }

    public function testAFailedServiceAccountAuthenticationIsReported(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 401]));

        $this->expectException(KeycloakRequestFailedException::class);

        (new KeycloakAdminClient($httpClient, 'http://keycloak:8080', 'specialday', 'specialday-api', 'wrong'))->deleteAccount(self::ACCOUNT_ID);
    }

    public function testATokenResponseWithoutAccessTokenIsReported(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['token_type' => 'Bearer']));

        $this->expectException(KeycloakRequestFailedException::class);

        (new KeycloakAdminClient($httpClient, 'http://keycloak:8080', 'specialday', 'specialday-api', 'secret'))->deleteAccount(self::ACCOUNT_ID);
    }
}
