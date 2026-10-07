<?php

namespace App\Tests\Infrastructure\Security\Keycloak;

use App\Infrastructure\Security\Keycloak\KeycloakAuthenticator;
use App\Infrastructure\Security\Keycloak\KeycloakClient;
use App\Infrastructure\Security\Keycloak\KeycloakLogoutListener;
use App\Infrastructure\Security\Keycloak\KeycloakUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class KeycloakAuthenticatorTest extends TestCase
{
    private MockHttpClient $httpClient;
    private KeycloakClient $keycloakClient;
    private UrlGeneratorInterface $urlGenerator;
    private KeycloakAuthenticator $authenticator;
    private Session $session;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->keycloakClient = new KeycloakClient($this->httpClient, 'http://localhost:8080', 'http://keycloak:8080', 'specialday', 'specialday-admin', 'secret');
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string => (UrlGeneratorInterface::ABSOLUTE_URL === $referenceType ? 'https://localhost' : '').match ($route) {
                'admin' => '/admin',
                'app_login' => '/admin/login',
                'app_login_check' => '/admin/login/check',
                default => throw new \LogicException($route),
            }
        );
        $this->urlGenerator = $urlGenerator;
        $this->authenticator = new KeycloakAuthenticator($this->keycloakClient, $this->urlGenerator);
        $this->session = new Session(new MockArraySessionStorage());
    }

    /**
     * @param array<string, string> $query
     */
    private function request(array $query = [], string $route = 'app_login_check'): Request
    {
        $request = new Request($query);
        $request->attributes->set('_route', $route);
        $request->setSession($this->session);

        return $request;
    }

    /**
     * Starts a login and returns the state Keycloak would send back.
     */
    private function startLogin(): string
    {
        $response = $this->authenticator->start($this->request(route: 'app_login'));
        parse_str((string) parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);
        self::assertIsString($query['state']);

        return $query['state'];
    }

    private function respondWithTokens(): void
    {
        $encode = static fn (string $json): string => rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $claims = ['email' => 'admin@specialday.local', 'realm_access' => ['roles' => ['ROLE_ADMIN']]];
        $this->httpClient->setResponseFactory(new JsonMockResponse([
            'access_token' => $encode('{}').'.'.$encode((string) json_encode($claims)).'.signature',
            'id_token' => 'the-id-token',
        ]));
    }

    public function testStartRedirectsToKeycloakWithTheCheckRouteAsRedirectUri(): void
    {
        $response = $this->authenticator->start($this->request(route: 'app_login'));

        self::assertStringStartsWith('http://localhost:8080/realms/specialday/protocol/openid-connect/auth?', $response->getTargetUrl());
        self::assertStringContainsString('redirect_uri='.urlencode('https://localhost/admin/login/check'), $response->getTargetUrl());
    }

    public function testSupportsOnlyTheCheckRoute(): void
    {
        self::assertTrue($this->authenticator->supports($this->request()));
        self::assertFalse($this->authenticator->supports($this->request(route: 'admin')));
    }

    public function testAuthenticatesTheKeycloakUser(): void
    {
        $state = $this->startLogin();
        $this->respondWithTokens();

        $passport = $this->authenticator->authenticate($this->request(['state' => $state, 'code' => 'the-code']));

        $user = $passport->getUser();
        self::assertInstanceOf(KeycloakUser::class, $user);
        self::assertSame('admin@specialday.local', $user->getUserIdentifier());
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());

        $token = $this->authenticator->createToken($passport, 'admin');
        self::assertSame('the-id-token', $token->getAttribute(KeycloakAuthenticator::ID_TOKEN_ATTRIBUTE));
    }

    public function testRejectsACallbackWithoutPendingLogin(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->authenticator->authenticate($this->request(['state' => 'forged', 'code' => 'the-code']));
    }

    public function testRejectsAForgedState(): void
    {
        $this->startLogin();

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->authenticator->authenticate($this->request(['state' => 'forged', 'code' => 'the-code']));
    }

    public function testRejectsAReplayedCallback(): void
    {
        $state = $this->startLogin();
        $this->respondWithTokens();
        $this->authenticator->authenticate($this->request(['state' => $state, 'code' => 'the-code']));

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->authenticator->authenticate($this->request(['state' => $state, 'code' => 'the-code']));
    }

    public function testRejectsAKeycloakError(): void
    {
        $state = $this->startLogin();

        $this->expectExceptionMessage('Keycloak a refusé la connexion.');

        $this->authenticator->authenticate($this->request(['state' => $state, 'error' => 'access_denied']));
    }

    public function testRejectsACallbackWithoutCode(): void
    {
        $state = $this->startLogin();

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->authenticator->authenticate($this->request(['state' => $state]));
    }

    public function testRejectsAFailedCodeExchange(): void
    {
        $state = $this->startLogin();
        $this->httpClient->setResponseFactory(new JsonMockResponse(['error' => 'invalid_grant'], ['http_code' => 400]));

        $this->expectExceptionMessage('La connexion avec Keycloak a échoué, veuillez réessayer.');

        $this->authenticator->authenticate($this->request(['state' => $state, 'code' => 'the-code']));
    }

    public function testSuccessRedirectsToTheTargetPathOnce(): void
    {
        $this->session->set('_security.admin.target_path', 'https://localhost/admin/company');

        $first = $this->authenticator->onAuthenticationSuccess($this->request(), new NullToken(), 'admin');
        $second = $this->authenticator->onAuthenticationSuccess($this->request(), new NullToken(), 'admin');

        self::assertInstanceOf(RedirectResponse::class, $first);
        self::assertSame('https://localhost/admin/company', $first->getTargetUrl());
        self::assertInstanceOf(RedirectResponse::class, $second);
        self::assertSame('/admin', $second->getTargetUrl());
    }

    public function testFailureStoresTheErrorForTheLoginPage(): void
    {
        $exception = new AuthenticationException();

        $response = $this->authenticator->onAuthenticationFailure($this->request(), $exception);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/login', $response->getTargetUrl());
        self::assertSame($exception, $this->session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR));
    }

    public function testLogoutEndsTheKeycloakSession(): void
    {
        $state = $this->startLogin();
        $this->respondWithTokens();
        $token = $this->authenticator->createToken(
            $this->authenticator->authenticate($this->request(['state' => $state, 'code' => 'the-code'])),
            'admin',
        );
        $event = new LogoutEvent($this->request(), $token);

        new KeycloakLogoutListener($this->keycloakClient, $this->urlGenerator)->onLogout($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(
            'http://localhost:8080/realms/specialday/protocol/openid-connect/logout?id_token_hint=the-id-token&post_logout_redirect_uri=https%3A%2F%2Flocalhost%2Fadmin%2Flogin',
            $response->getTargetUrl(),
        );
    }

    public function testLogoutWithoutKeycloakTokenKeepsTheDefaultResponse(): void
    {
        $event = new LogoutEvent($this->request(), new NullToken());

        new KeycloakLogoutListener($this->keycloakClient, $this->urlGenerator)->onLogout($event);

        self::assertNull($event->getResponse());
    }
}
