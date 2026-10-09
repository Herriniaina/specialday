<?php

namespace App\Infrastructure\Company\Http;

use App\Domain\Company\Company;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CurrentCompanyValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<?Company>
     */
    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ([] === $argument->getAttributesOfType(CurrentCompany::class)) {
            return [];
        }

        $company = $request->attributes->get(CompanySubdomainListener::ATTRIBUTE);
        if ($company instanceof Company) {
            return [$company];
        }

        if ($argument->isNullable()) {
            return [null];
        }

        throw new NotFoundHttpException('This resource is only available on a company subdomain.');
    }
}
