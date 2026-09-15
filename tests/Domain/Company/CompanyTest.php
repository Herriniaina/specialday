<?php

namespace App\Tests\Domain\Company;

use App\Domain\Company\Company;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    public function testConstructorAndGettersExposeProvidedValues(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');

        $company = new Company(
            id: 'company-id',
            name: 'Acme Corp',
            slug: 'acme-corp',
            createdAt: $createdAt,
            nif: 'NIF123',
            stat: 'STAT456',
            rcs: 'RCS789',
            phone: '+261340000000',
            instagramProfile: 'acme.ig',
            facebookProfile: 'acme.fb',
            youtubeProfile: 'acme.yt',
        );

        self::assertSame('company-id', $company->getId());
        self::assertSame('Acme Corp', $company->getName());
        self::assertSame('acme-corp', $company->getSlug());
        self::assertSame($createdAt, $company->getCreatedAt());
        self::assertSame('NIF123', $company->getNif());
        self::assertSame('STAT456', $company->getStat());
        self::assertSame('RCS789', $company->getRcs());
        self::assertSame('+261340000000', $company->getPhone());
        self::assertSame('acme.ig', $company->getInstagramProfile());
        self::assertSame('acme.fb', $company->getFacebookProfile());
        self::assertSame('acme.yt', $company->getYoutubeProfile());
    }

    public function testOptionalFieldsDefaultToNull(): void
    {
        $company = new Company(
            id: 'company-id',
            name: 'Acme Corp',
            slug: 'acme-corp',
            createdAt: new \DateTimeImmutable(),
        );

        self::assertNull($company->getNif());
        self::assertNull($company->getStat());
        self::assertNull($company->getRcs());
        self::assertNull($company->getPhone());
        self::assertNull($company->getInstagramProfile());
        self::assertNull($company->getFacebookProfile());
        self::assertNull($company->getYoutubeProfile());
    }
}
