<?php

namespace App\Tests\Infrastructure\Company\Http;

use App\Application\Company\GetCompanyBySlug;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Infrastructure\Company\Http\CompanySubdomainListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CompanySubdomainListenerTest extends TestCase
{
    private CompanyRepositoryInterface&MockObject $companyRepository;
    private CompanySubdomainListener $listener;

    protected function setUp(): void
    {
        $this->companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $this->listener = new CompanySubdomainListener(new GetCompanyBySlug($this->companyRepository), 'specialday.local');
    }

    public function testStoresTheCompanyMatchingTheSubdomain(): void
    {
        $company = new Company('company-id', 'Photo Company', 'photo-company', new \DateTimeImmutable());
        $this->companyRepository->expects(self::once())
            ->method('findBySlug')
            ->with('photo-company')
            ->willReturn($company);

        $request = Request::create('https://www.photo-company.specialday.local/api/public/media');
        $this->listener->__invoke($this->event($request));

        self::assertSame($company, $request->attributes->get(CompanySubdomainListener::ATTRIBUTE));
    }

    public function testMatchesTheHostCaseInsensitively(): void
    {
        $this->companyRepository->expects(self::once())
            ->method('findBySlug')
            ->with('photo-company')
            ->willReturn(new Company('company-id', 'Photo Company', 'photo-company', new \DateTimeImmutable()));

        $this->listener->__invoke($this->event(Request::create('https://WWW.Photo-Company.SpecialDay.local/')));
    }

    public function testThrowsNotFoundForAnUnknownCompany(): void
    {
        $this->companyRepository->expects(self::once())
            ->method('findBySlug')
            ->with('unknown')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        $this->listener->__invoke($this->event(Request::create('https://www.unknown.specialday.local/')));
    }

    public function testThrowsNotFoundForTheBackOfficeOnACompanySubdomain(): void
    {
        $this->companyRepository->expects(self::never())->method('findBySlug');

        $this->expectException(NotFoundHttpException::class);

        $this->listener->__invoke($this->event(Request::create('https://www.photo-company.specialday.local/admin/company')));
    }

    #[DataProvider('hostsWithoutCompanyProvider')]
    public function testIgnoresHostsWithoutCompany(string $url): void
    {
        $this->companyRepository->expects(self::never())->method('findBySlug');

        $request = Request::create($url);
        $this->listener->__invoke($this->event($request));

        self::assertFalse($request->attributes->has(CompanySubdomainListener::ATTRIBUTE));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function hostsWithoutCompanyProvider(): iterable
    {
        yield 'main host' => ['https://www.specialday.local/admin'];
        yield 'internal host' => ['http://php/api/public/company'];
        yield 'other domain' => ['https://www.photo-company.example.com/'];
        yield 'missing www' => ['https://photo-company.specialday.local/'];
        yield 'nested subdomain' => ['https://www.a.b.specialday.local/'];
        yield 'domain as substring' => ['https://www.photo-company.specialday.local.evil.com/'];
    }

    public function testIgnoresSubRequests(): void
    {
        $this->companyRepository->expects(self::never())->method('findBySlug');

        $request = Request::create('https://www.photo-company.specialday.local/');
        $this->listener->__invoke(new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::SUB_REQUEST));

        self::assertFalse($request->attributes->has(CompanySubdomainListener::ATTRIBUTE));
    }

    private function event(Request $request): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
