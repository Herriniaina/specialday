<?php

namespace App\Tests\Infrastructure\Company\Http;

use App\Domain\Company\Company;
use App\Infrastructure\Company\Http\CompanySubdomainListener;
use App\Infrastructure\Company\Http\CurrentCompany;
use App\Infrastructure\Company\Http\CurrentCompanyValueResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrentCompanyValueResolverTest extends TestCase
{
    private CurrentCompanyValueResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CurrentCompanyValueResolver();
    }

    public function testIgnoresArgumentsWithoutTheAttribute(): void
    {
        $request = new Request();
        $request->attributes->set(CompanySubdomainListener::ATTRIBUTE, $this->company());

        self::assertSame([], $this->resolver->resolve($request, $this->argument(attributes: [])));
    }

    public function testResolvesTheSubdomainCompany(): void
    {
        $company = $this->company();
        $request = new Request();
        $request->attributes->set(CompanySubdomainListener::ATTRIBUTE, $company);

        self::assertSame([$company], $this->resolver->resolve($request, $this->argument()));
    }

    public function testResolvesNullOnTheMainHostForANullableArgument(): void
    {
        self::assertSame([null], $this->resolver->resolve(new Request(), $this->argument(nullable: true)));
    }

    public function testThrowsNotFoundOnTheMainHostForARequiredArgument(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->resolver->resolve(new Request(), $this->argument());
    }

    /**
     * @param object[] $attributes
     */
    private function argument(bool $nullable = false, ?array $attributes = null): ArgumentMetadata
    {
        return new ArgumentMetadata('company', Company::class, false, false, null, $nullable, $attributes ?? [new CurrentCompany()]);
    }

    private function company(): Company
    {
        return new Company('company-id', 'Photo Company', 'photo-company', new \DateTimeImmutable());
    }
}
