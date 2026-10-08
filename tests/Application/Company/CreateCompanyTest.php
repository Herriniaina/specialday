<?php

namespace App\Tests\Application\Company;

use App\Application\Company\CreateCompany;
use App\Application\Company\CreateCompanyRequest;
use App\Application\Company\UserAlreadyHasCompanyException;
use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\SlugGeneratorInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class CreateCompanyTest extends TestCase
{
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private IdGeneratorInterface&Stub $idGenerator;
    private SlugGeneratorInterface&MockObject $slugGenerator;
    private UserRepositoryInterface&MockObject $userRepository;
    private CreateCompany $createCompany;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->idGenerator = $this->createStub(IdGeneratorInterface::class);
        $this->slugGenerator = $this->createMock(SlugGeneratorInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $transaction = $this->createStub(TransactionInterface::class);
        $transaction->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());

        $this->idGenerator->method('generate')->willReturn('generated-id');

        $this->createCompany = new CreateCompany(
            $this->companyRepository,
            $this->idGenerator,
            $this->slugGenerator,
            $this->userRepository,
            $transaction,
        );
    }

    private static function owner(?string $companyId = null): User
    {
        return new User('keycloak-id', $companyId, 'owner-id');
    }

    public function testExecuteCreatesAndSavesCompanyWithGeneratedIdAndSlug(): void
    {
        $request = new CreateCompanyRequest(ownerId: 'owner-id', name: 'Acme Corp', phone: '+261340000000');

        $this->slugGenerator->expects(self::once())->method('slugify')->with('Acme Corp')->willReturn('acme-corp');
        $this->userRepository->expects(self::once())->method('findById')->with('owner-id')->willReturn(self::owner());
        $this->userRepository->expects(self::once())
            ->method('attachToCompany')
            ->with('owner-id', 'generated-id')
            ->willReturn(true);
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
        $request = new CreateCompanyRequest(ownerId: 'owner-id', name: 'Acme Corp');

        $this->slugGenerator->expects(self::once())->method('slugify')->willReturn('acme-corp');
        $this->userRepository->method('findById')->willReturn(self::owner());
        $this->userRepository->expects(self::once())->method('attachToCompany')->willReturn(true);

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

    public function testExecuteThrowsWhenTheOwnerAlreadyHasACompany(): void
    {
        $this->userRepository->method('findById')->willReturn(self::owner('existing-company-id'));

        $this->slugGenerator->expects(self::never())->method('slugify');
        $this->companyRepository->expects(self::never())->method('save');
        $this->userRepository->expects(self::never())->method('attachToCompany');

        $this->expectException(UserAlreadyHasCompanyException::class);

        $this->createCompany->execute(new CreateCompanyRequest(ownerId: 'owner-id', name: 'Acme Corp'));
    }

    public function testExecuteFailsWhenACompanyWasAttachedConcurrently(): void
    {
        $this->slugGenerator->expects(self::once())->method('slugify')->willReturn('acme-corp');
        $this->userRepository->method('findById')->willReturn(self::owner());

        $this->companyRepository->expects(self::once())->method('save');
        $this->userRepository->expects(self::once())->method('attachToCompany')->willReturn(false);

        // Thrown inside the transaction: the company saved just before is rolled back.
        $this->expectException(UserAlreadyHasCompanyException::class);

        $this->createCompany->execute(new CreateCompanyRequest(ownerId: 'owner-id', name: 'Acme Corp'));
    }
}
