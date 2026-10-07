<?php

namespace App\Infrastructure\Security\Keycloak;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Back-office login through Keycloak: the entry point redirects to Keycloak's login page, which
 * sends the browser back to the "app_login_check" route, where the code is exchanged for tokens.
 */
final class KeycloakAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public const string CHECK_ROUTE = 'app_login_check';
    /** Token attribute holding the ID token, sent back to Keycloak as id_token_hint on logout. */
    public const string ID_TOKEN_ATTRIBUTE = 'keycloak_id_token';
    private const string SESSION_KEY = '_keycloak_authorization';
    private const string LOGIN_ROUTE = 'app_login';
    private const string DEFAULT_TARGET_ROUTE = 'admin';

    public function __construct(
        private readonly KeycloakClient $keycloakClient,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $authorization = $this->keycloakClient->createAuthorization($this->redirectUri());
        $request->getSession()->set(self::SESSION_KEY, [
            'state' => $authorization->state,
            'code_verifier' => $authorization->codeVerifier,
        ]);

        return new RedirectResponse($authorization->url);
    }

    public function supports(Request $request): bool
    {
        return self::CHECK_ROUTE === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        // One-shot: a callback can never be replayed against the same authorization request.
        $pending = $request->getSession()->remove(self::SESSION_KEY);
        $state = $request->query->getString('state');

        if (!is_array($pending) || !is_string($pending['state'] ?? null) || !is_string($pending['code_verifier'] ?? null)
            || '' === $state || !hash_equals($pending['state'], $state)) {
            throw new CustomUserMessageAuthenticationException('La demande de connexion a expiré ou est invalide, veuillez réessayer.');
        }

        if ($request->query->has('error')) {
            throw new CustomUserMessageAuthenticationException('Keycloak a refusé la connexion.');
        }

        $code = $request->query->getString('code');
        if ('' === $code) {
            throw new CustomUserMessageAuthenticationException('Keycloak n\'a renvoyé aucun code d\'autorisation.');
        }

        try {
            $tokens = $this->keycloakClient->exchangeCode($code, $this->redirectUri(), $pending['code_verifier']);
            $user = KeycloakUser::fromClaims($tokens->accessTokenClaims);
        } catch (KeycloakRequestFailedException $exception) {
            throw new CustomUserMessageAuthenticationException('La connexion avec Keycloak a échoué, veuillez réessayer.', previous: $exception);
        }

        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn (): KeycloakUser => $user));
        $passport->setAttribute(self::ID_TOKEN_ATTRIBUTE, $tokens->idToken);

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $token = parent::createToken($passport, $firewallName);
        $token->setAttribute(self::ID_TOKEN_ATTRIBUTE, $passport->getAttribute(self::ID_TOKEN_ATTRIBUTE));

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        $this->removeTargetPath($request->getSession(), $firewallName);

        return new RedirectResponse($targetPath ?? $this->urlGenerator->generate(self::DEFAULT_TARGET_ROUTE));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Shown by the login page, which does not redirect to Keycloak again: no redirect loop.
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    private function redirectUri(): string
    {
        return $this->urlGenerator->generate(self::CHECK_ROUTE, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
