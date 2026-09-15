<?php

namespace App\Tests\Application\Company;

use App\Application\Company\CreateCompany;
use App\Application\Company\CreateCompanyRequest;
use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\SlugGeneratorInterface;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class CreateCompanyTest extends TestCase
{
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private IdGeneratorInterface&Stub $idGenerator;
    private SlugGeneratorInterface&MockObject $slugGenerator;
    private CreateCompany $createCompany;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->idGenerator = $this->createStub(IdGeneratorInterface::class);
        $this->slugGenerator = $this->createMock(SlugGeneratorInterface::class);

        $this->idGenerator->method('generate')->willReturn('generated-id');
        $this->slugGenerator->expects(self::once())->method('slugify')->with('Acme Corp')->willReturn('acme-corp');

        $this->createCompany = new CreateCompany(
            $this->companyRepository,
            $this->idGenerator,
            $this->slugGenerator,
        );
    }

    public function testExecuteCreatesAndSavesCompanyWithGeneratedIdAndSlug(): void
    {
        $request = new CreateCompanyRequest(name: 'Acme Corp', phone: '+261340000000');

        $this->companyRepository->expects(self::once())->method('existsBySlug')->with('acme-corp')->willReturn(false);

        $this->companyRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Company $company): bool {
                self::assertSame('generated-id', $company->getId());
                self::assertSame('Acme Corp', $company->getName());
                self::assertSame('acme-corp', $company->getSlug());
                self::assertSame('+261340000000', $company->getPhone());

                return true;
            }));

        $company = $this->createCompany->execute($request);

        self::assertSame('acme-corp', $company->getSlug());
    }

    public function testExecuteAppendsIncrementingSuffixUntilSlugIsUnique(): void
    {
        $request = new CreateCompanyRequest(name: 'Acme Corp');

        $this->idGenerator->method('generate')->willReturn('generated-id');
        $this->slugGenerator->method('slugify')->willReturn('acme-corp');

        $this->companyRepository->method('existsBySlug')->willReturnMap([
            ['acme-corp', true],
            ['acme-corp-2', true],
            ['acme-corp-3', false],
        ]);

        $this->companyRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(fn (Company $company): bool => 'acme-corp-3' === $company->getSlug()));

        $company = $this->createCompany->execute($request);

        self::assertSame('acme-corp-3', $company->getSlug());
    }
}
