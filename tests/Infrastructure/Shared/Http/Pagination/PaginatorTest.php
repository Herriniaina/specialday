<?php

namespace App\Tests\Infrastructure\Shared\Http\Pagination;

use App\Infrastructure\Shared\Http\Pagination\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testToArrayComputesTheTotalPageCount(): void
    {
        $paginator = new Paginator(page: 2, limit: 10, total: 25);

        self::assertSame(['page' => 2, 'limit' => 10, 'total' => 25, 'total_pages' => 3], $paginator->toArray());
    }

    public function testToArrayRoundsTheTotalPageCountUp(): void
    {
        $paginator = new Paginator(page: 1, limit: 20, total: 1);

        self::assertSame(1, $paginator->toArray()['total_pages']);
    }

    public function testToArrayReturnsZeroTotalPagesWhenThereAreNoResults(): void
    {
        $paginator = new Paginator(page: 1, limit: 20, total: 0);

        self::assertSame(0, $paginator->toArray()['total_pages']);
    }

    public function testToArrayReturnsZeroTotalPagesWhenTheLimitIsZero(): void
    {
        $paginator = new Paginator(page: 1, limit: 0, total: 5);

        self::assertSame(0, $paginator->toArray()['total_pages']);
    }
}
