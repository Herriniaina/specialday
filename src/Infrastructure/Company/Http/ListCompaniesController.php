<?php

namespace App\Infrastructure\Company\Http;

use App\Application\Company\ListCompanies;
use App\Application\Company\ListCompaniesRequest;
use App\Domain\Company\Company;
use App\Domain\Shared\FilterOperator;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use App\Infrastructure\Shared\Http\Filter\FilterQueryParser;
use App\Infrastructure\Shared\Http\Filter\InvalidFilterOperatorException;
use App\Infrastructure\Shared\Http\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ListCompaniesController extends ApiAbstractController
{
    public function __construct(
        private ListCompanies $listCompanies,
    ) {
    }

    #[Route('/api/public/company', name: 'app_public_company_list', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ?ListCompaniesInput $input): JsonResponse
    {
        $input ??= new ListCompaniesInput();

        $equalityOperators = [FilterOperator::EQ, FilterOperator::NOT_EQ, FilterOperator::IN, FilterOperator::NOT_IN];

        try {
            $nameFilter = null !== $input->name ? FilterQueryParser::parse($input->name, $equalityOperators) : null;
        } catch (InvalidFilterOperatorException $e) {
            return $this->badRequest('company.list.filter.invalid', ['name' => $e->getMessage()]);
        }

        try {
            $prestationFilter = null !== $input->prestation ? FilterQueryParser::parse($input->prestation, $equalityOperators) : null;
        } catch (InvalidFilterOperatorException $e) {
            return $this->badRequest('company.list.filter.invalid', ['prestation' => $e->getMessage()]);
        }

        try {
            $notesFilter = null !== $input->notes ? FilterQueryParser::parse($input->notes, [FilterOperator::CONTAINS]) : null;
        } catch (InvalidFilterOperatorException $e) {
            return $this->badRequest('company.list.filter.invalid', ['notes' => $e->getMessage()]);
        }

        $result = $this->listCompanies->execute(new ListCompaniesRequest(
            nameFilter: $nameFilter,
            prestationFilter: $prestationFilter,
            notesFilter: $notesFilter,
            page: $input->page,
            limit: $input->limit,
        ));

        return $this->success([
            'items' => array_map(
                fn (Company $company): array => [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                    'slug' => $company->getSlug(),
                    'phone' => $company->getPhone(),
                    'instagram_profile' => $company->getInstagramProfile(),
                    'facebook_profile' => $company->getFacebookProfile(),
                    'youtube_profile' => $company->getYoutubeProfile(),
                ],
                $result->items,
            ),
            'pagination' => new Paginator($result->page, $result->limit, $result->total)->toArray(),
        ]);
    }
}
