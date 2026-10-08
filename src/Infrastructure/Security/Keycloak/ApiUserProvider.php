<?php

namespace App\Infrastructure\Security\Keycloak;

use App\Domain\User\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Called by the "oidc" access token handler of the api firewall once the token is verified, with
 * the "sub" claim as identifier and every claim as attributes.
 *
 * @implements AttributesBasedUserProviderInterface<ApiUser>
 */
final readonly class ApiUserProvider implements AttributesBasedUserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @param array<mixed> $attributes the access token claims
     */
    public function loadUserByIdentifier(string $identifier, array $attributes = []): UserInterface
    {
        $user = $this->userRepository->findByIdentityProviderId($identifier);
        $userId = $user?->getId();

        // A valid Keycloak account without local user (e.g. created from the Keycloak console)
        // cannot use the API: there is no user to attach its actions to.
        if (null === $user || null === $userId || '' === $identifier) {
            $exception = new UserNotFoundException(sprintf('No user is linked to the Keycloak account "%s".', $identifier));
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return new ApiUser(
            $identifier,
            $userId,
            $user->getCompanyId(),
            KeycloakRoles::fromClaims($attributes),
            email: self::claim($attributes, 'email'),
            firstName: self::claim($attributes, 'given_name'),
            lastName: self::claim($attributes, 'family_name'),
            phoneNumber: self::claim($attributes, 'phone_number'),
        );
    }

    /**
     * @param array<mixed> $claims
     */
    private static function claim(array $claims, string $name): ?string
    {
        return is_string($claims[$name] ?? null) ? $claims[$name] : null;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof ApiUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        // Stateless firewall: never called in practice, the user is rebuilt from each token.
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return ApiUser::class === $class;
    }
}
