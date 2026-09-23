<?php

namespace App\Tests\Application\Company;

use App\Application\Company\ListCompanies;
use App\Application\Company\ListCompaniesRequest;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Company\CompanySearchCriteria;
use App\Domain\Company\CompanySearchResult;
use App\Domain\Shared\Filter;
use App\Domain\Shared\FilterOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListCompaniesTest extends TestCase
{
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private ListCompanies $listCompanies;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->listCompanies = new ListCompanies($this->companyRepository);
    }

    public function testExecuteForwardsFiltersAndPaginationToTheRepository(): void
    {
        $nameFilter = new Filter(FilterOperator::EQ, ['Nom company']);
        $prestationFilter = new Filter(FilterOperator::IN, ['photo', 'video']);
        $notesFilter = new Filter(FilterOperator::CONTAINS, ['lenses']);

        $expected = new CompanySearchResult(items: [], total: 0, page: 2, limit: 10);

        $this->companyRepository->expects(self::once())
            ->method('search')
            ->with(self::callback(function (CompanySearchCriteria $criteria) use ($nameFilter, $prestationFilter, $notesFilter): bool {
                self::assertSame($nameFilter, $criteria->nameFilter);
                self::assertSame($prestationFilter, $criteria->prestationFilter);
                self::assertSame($notesFilter, $criteria->notesFilter);
                self::assertSame(2, $criteria->page);
                self::assertSame(10, $criteria->limit);

                return true;
            }))
            ->willReturn($expected);

        $result = $this->listCompanies->execute(new ListCompaniesRequest(
            nameFilter: $nameFilter,
            prestationFilter: $prestationFilter,
            notesFilter: $notesFilter,
            page: 2,
            limit: 10,
        ));

        self::assertSame($expected, $result);
    }

    public function testExecuteDefaultsToPageOneWhenAZeroOrNegativePageIsGiven(): void
    {
        $this->companyRepository->expects(self::once())
            ->method('search')
            ->willReturnCallback(fn (CompanySearchCriteria $criteria): CompanySearchResult => new CompanySearchResult(
                items: [],
                total: 0,
                page: $criteria->page,
                limit: $criteria->limit,
            ));

        $result = $this->listCompanies->execute(new ListCompaniesRequest(page: 0, limit: 20));

        self::assertSame(1, $result->page);
    }

    public function testExecuteReturnsTheCompaniesFromTheRepository(): void
    {
        $company = new Company(
            id: 'company-id',
            name: 'Nom company',
            slug: 'nom-company',
            createdAt: new \DateTimeImmutable(),
        );

        $this->companyRepository->expects(self::once())->method('search')->willReturn(new CompanySearchResult(
            items: [$company],
            total: 1,
            page: 1,
            limit: 20,
        ));

        $result = $this->listCompanies->execute(new ListCompaniesRequest());

        self::assertSame([$company], $result->items);
        self::assertSame(1, $result->total);
    }
}
