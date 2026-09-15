<?php

namespace App\Tests\Infrastructure\Shared\Identity;

use App\Infrastructure\Shared\Identity\UuidV7IdGenerator;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UuidV7IdGeneratorTest extends TestCase
{
    public function testGenerateReturnsAValidUuidV7String(): void
    {
        $generator = new UuidV7IdGenerator();

        $id = $generator->generate();

        self::assertTrue(Uuid::isValid($id));
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id,
        );
    }

    public function testGenerateReturnsDifferentValuesOnEachCall(): void
    {
        $generator = new UuidV7IdGenerator();

        self::assertNotSame($generator->generate(), $generator->generate());
    }
}
