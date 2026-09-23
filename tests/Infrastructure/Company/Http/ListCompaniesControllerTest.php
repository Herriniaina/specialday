<?php

namespace App\Tests\Infrastructure\Company\Http;

use App\Application\Company\ListCompanies;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Company\CompanySearchCriteria;
use App\Domain\Company\CompanySearchResult;
use App\Domain\Shared\FilterOperator;
use App\Infrastructure\Company\Http\ListCompaniesController;
use App\Infrastructure\Company\Http\ListCompaniesInput;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ListCompaniesControllerTest extends TestCase
{
    private CompanyRepositoryInterface&Stub $companyRepository;
    private ListCompaniesController $controller;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createStub(CompanyRepositoryInterface::class);
        $this->controller = new ListCompaniesController(new ListCompanies($this->companyRepository));
    }

    /**
     * @return array<string, mixed>
     */
    private function body(JsonResponse $response): array
    {
        $body = json_decode((string) $response->getContent(), true);
        self::assertIsArray($body);

        return $body;
    }

    public function testReturnsTheCompaniesForTheDefaultPaginationWhenNoInputIsGiven(): void
    {
        $company = new Company(id: 'company-id', name: 'Nom company', slug: 'nom-company', createdAt: new \DateTimeImmutable());

        $this->companyRepository->method('search')->willReturn(new CompanySearchResult(
            items: [$company],
            total: 1,
            page: 1,
            limit: 20,
        ));

        $response = ($this->controller)(null);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = $this->body($response)['payload'];
        self::assertSame('company-id', $payload['items'][0]['id']);
        self::assertSame(['page' => 1, 'limit' => 20, 'total' => 1, 'total_pages' => 1], $payload['pagination']);
    }

    public function testParsesTheNameAndPrestationFiltersFromTheInput(): void
    {
        $captured = null;
        $this->companyRepository->method('search')->willReturnCallback(
            function (CompanySearchCriteria $criteria) use (&$captured): CompanySearchResult {
                $captured = $criteria;

                return new CompanySearchResult(items: [], total: 0, page: $criteria->page, limit: $criteria->limit);
            }
        );

        $input = new ListCompaniesInput(name: 'eq:Nom company', prestation: 'in:photo,video', notes: 'contains:lenses', page: 2, limit: 10);

        ($this->controller)($input);

        self::assertInstanceOf(CompanySearchCriteria::class, $captured);
        self::assertSame(FilterOperator::EQ, $captured->nameFilter?->operator);
        self::assertSame(['Nom company'], $captured->nameFilter?->values);
        self::assertSame(FilterOperator::IN, $captured->prestationFilter?->operator);
        self::assertSame(['photo', 'video'], $captured->prestationFilter?->values);
        self::assertSame(FilterOperator::CONTAINS, $captured->notesFilter?->operator);
        self::assertSame(['lenses'], $captured->notesFilter?->values);
        self::assertSame(2, $captured->page);
        self::assertSame(10, $captured->limit);
    }

    public function testReturnsBadRequestWhenTheNameFilterOperatorIsUnknown(): void
    {
        $input = new ListCompaniesInput(name: 'like:Nom company');

        $response = ($this->controller)($input);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('company.list.filter.invalid', $this->body($response)['statusText']);
        self::assertArrayHasKey('name', $this->body($response)['payload']);
    }

    public function testReturnsBadRequestWhenThePrestationFilterOperatorIsUnknown(): void
    {
        $input = new ListCompaniesInput(prestation: 'like:photo');

        $response = ($this->controller)($input);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('company.list.filter.invalid', $this->body($response)['statusText']);
        self::assertArrayHasKey('prestation', $this->body($response)['payload']);
    }

    public function testReturnsBadRequestWhenTheNameFilterUsesContains(): void
    {
        $input = new ListCompaniesInput(name: 'contains:Nom');

        $response = ($this->controller)($input);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('name', $this->body($response)['payload']);
    }

    public function testReturnsBadRequestWhenTheNotesFilterOperatorIsNotContains(): void
    {
        $input = new ListCompaniesInput(notes: 'eq:lenses');

        $response = ($this->controller)($input);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('company.list.filter.invalid', $this->body($response)['statusText']);
        self::assertArrayHasKey('notes', $this->body($response)['payload']);
    }
}
