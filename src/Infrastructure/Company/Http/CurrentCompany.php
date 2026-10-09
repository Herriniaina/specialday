<?php

namespace App\Infrastructure\Company\Http;

/**
 * Injects into a controller argument typed App\Domain\Company\Company the company resolved from
 * the request's subdomain (see CompanySubdomainListener). Make the argument nullable to also
 * accept requests made on the main host.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class CurrentCompany
{
}
