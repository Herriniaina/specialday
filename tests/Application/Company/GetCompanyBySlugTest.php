<?php

namespace App\Tests\Application\Company;

use App\Application\Company\GetCompanyBySlug;
use App\Application\Company\GetCompanyBySlugRequest;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Company\CompanyRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetCompanyBySlugTest extends TestCase
{
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private GetCompanyBySlug $getCompanyBySlug;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->getCompanyBySlug = new GetCompanyBySlug($this->companyRepository);
    }

    public function testExecuteThrowsWhenCompanyDoesNotExist(): void
    {
        $this->companyRepository->expects(self::once())
            ->method('findBySlug')
            ->with('unknown-company')
            ->willReturn(null);

        $this->expectException(CompanyNotFoundException::class);

        $this->getCompanyBySlug->execute(new GetCompanyBySlugRequest('unknown-company'));
    }

    public function testExecuteReturnsTheMatchedCompany(): void
    {
        $company = new Company('company-id', 'Photo Company', 'photo-company', new \DateTimeImmutable());

        $this->companyRepository->expects(self::once())
            ->method('findBySlug')
            ->with('photo-company')
            ->willReturn($company);

        self::assertSame($company, $this->getCompanyBySlug->execute(new GetCompanyBySlugRequest('photo-company')));
    }
}
