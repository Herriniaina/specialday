<?php

namespace App\Infrastructure\Company\Http;

use App\Application\Company\GetCompanyBySlug;
use App\Application\Company\GetCompanyBySlugRequest;
use App\Domain\Company\CompanyNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resolves the company a request is addressed to from its host: www.<company slug>.<APP_DOMAIN>
 * (e.g. www.photo-company.specialday.local). The company is stored in the request attributes,
 * where #[CurrentCompany] reads it, and CompanyAccessVoter checks the API caller belongs to it. The
 * main host (www.<APP_DOMAIN>) carries no company. The back-office only lives on the main host.
 *
 * Runs after the router (32) and before the firewall (8), so security can rely on it.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 24)]
final class CompanySubdomainListener
{
    public const string ATTRIBUTE = '_company';

    private string $hostPattern;

    public function __construct(
        private GetCompanyBySlug $getCompanyBySlug,
        #[Autowire(env: 'APP_DOMAIN')]
        string $appDomain,
    ) {
        $this->hostPattern = '/^www\.(?<slug>[a-z0-9]+(?:-[a-z0-9]+)*)\.'.preg_quote(strtolower($appDomain), '/').'$/';
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (1 !== preg_match($this->hostPattern, $request->getHost(), $matches)) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/admin')) {
            throw new NotFoundHttpException('The back-office is only available on the main host.');
        }

        try {
            $company = $this->getCompanyBySlug->execute(new GetCompanyBySlugRequest($matches['slug']));
        } catch (CompanyNotFoundException $e) {
            throw new NotFoundHttpException('Company not found.', $e);
        }

        $request->attributes->set(self::ATTRIBUTE, $company);
    }
}
