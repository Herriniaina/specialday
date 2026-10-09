<?php

namespace App\Infrastructure\Security\Voter;

use App\Domain\Company\Company;
use App\Infrastructure\Company\Http\CompanySubdomainListener;
use App\Infrastructure\Security\Keycloak\ApiUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Access rule of every private /api route (access_control in config/packages/security.yaml, so a
 * route can't forget it): the caller must be authenticated by an API token and, on a company's
 * subdomain (www.<slug>.<APP_DOMAIN>), belong to that company — guarding against IDOR. A request on
 * the main host carries no company: being authenticated is enough. An anonymous caller is denied,
 * which makes the firewall answer with its entry point (JSON 401).
 *
 * @extends Voter<string, Request>
 */
final class CompanyAccessVoter extends Voter
{
    public const string ACCESS = 'COMPANY_ACCESS';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ACCESS === $attribute && $subject instanceof Request;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof ApiUser) {
            return false;
        }

        $company = $subject->attributes->get(CompanySubdomainListener::ATTRIBUTE);

        return !$company instanceof Company || $user->companyId === $company->getId();
    }
}
