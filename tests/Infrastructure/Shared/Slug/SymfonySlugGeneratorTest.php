<?php

namespace App\Tests\Infrastructure\Shared\Slug;

use App\Infrastructure\Shared\Slug\SymfonySlugGenerator;
use PHPUnit\Framework\TestCase;

class SymfonySlugGeneratorTest extends TestCase
{
    public function testSlugifyReturnsLowercasedAsciiSlug(): void
    {
        $generator = new SymfonySlugGenerator();

        self::assertSame('acme-corp', $generator->slugify('Acme Corp'));
    }

    public function testSlugifyTransliteratesAccentedCharacters(): void
    {
        $generator = new SymfonySlugGenerator();

        self::assertSame('societe-generale', $generator->slugify('Société Générale'));
    }
}
