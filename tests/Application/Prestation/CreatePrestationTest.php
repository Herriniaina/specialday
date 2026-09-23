<?php

namespace App\Tests\Application\Prestation;

use App\Application\Prestation\CreatePrestation;
use App\Application\Prestation\CreatePrestationRequest;
use App\Application\Shared\IdGeneratorInterface;
use App\Domain\Prestation\Prestation;
use App\Domain\Prestation\PrestationRepositoryInterface;
use App\Domain\PrestationCategory\PrestationCategoryNotFoundException;
use App\Domain\PrestationCategory\PrestationCategoryRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class CreatePrestationTest extends TestCase
{
    private PrestationRepositoryInterface&MockObject $prestationRepository;
    private PrestationCategoryRepositoryInterface&MockObject $prestationCategoryRepository;
    private IdGeneratorInterface&Stub $idGenerator;
    private CreatePrestation $createPrestation;

    protected function setUp(): void
    {
        $this->prestationRepository = $this->createMock(PrestationRepositoryInterface::class);
        $this->prestationCategoryRepository = $this->createMock(PrestationCategoryRepositoryInterface::class);
        $this->idGenerator = $this->createStub(IdGeneratorInterface::class);

        $this->idGenerator->method('generate')->willReturn('generated-id');

        $this->createPrestation = new CreatePrestation(
            $this->prestationRepository,
            $this->prestationCategoryRepository,
            $this->idGenerator,
        );
    }

    public function testExecuteCreatesAndSavesPrestationWithGeneratedId(): void
    {
        $request = new CreatePrestationRequest(
            companyId: 'company-id',
            prestationCategoryId: 'category-id',
            amount: 1000,
            currency: 'Ar',
            taxIncluded: true,
            quantity: 1,
            unit: 'photo',
        );

        $this->prestationCategoryRepository->expects(self::once())
            ->method('existsById')
            ->with('category-id')
            ->willReturn(true);

        $this->prestationRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Prestation $prestation): bool {
                self::assertSame('generated-id', $prestation->getId());
                self::assertSame('company-id', $prestation->companyId);
                self::assertSame('category-id', $prestation->prestationCategoryId);
                self::assertSame(1000, $prestation->price->amount);
                self::assertSame('Ar', $prestation->price->currency);
                self::assertTrue($prestation->price->taxIncluded);
                self::assertSame(1, $prestation->quantity);
                self::assertSame('photo', $prestation->unit);

                return true;
            }));

        $prestation = $this->createPrestation->execute($request);

        self::assertSame('generated-id', $prestation->getId());
    }

    public function testExecuteThrowsWhenPrestationCategoryDoesNotExist(): void
    {
        $request = new CreatePrestationRequest(
            companyId: 'company-id',
            prestationCategoryId: 'missing-category-id',
            amount: 1000,
            currency: 'Ar',
            taxIncluded: true,
            quantity: 1,
            unit: 'photo',
        );

        $this->prestationCategoryRepository->method('existsById')->willReturn(false);

        $this->prestationRepository->expects(self::never())->method('save');

        $this->expectException(PrestationCategoryNotFoundException::class);

        $this->createPrestation->execute($request);
    }
}
