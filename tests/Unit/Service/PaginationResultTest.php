<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PaginationResult;
use PHPUnit\Framework\TestCase;

final class PaginationResultTest extends TestCase
{
    public function testMiddlePage(): void
    {
        $result = new PaginationResult(items: ['a', 'b'], page: 2, pages: 3, total: 60, perPage: 25);

        self::assertSame(['a', 'b'], $result->items);
        self::assertTrue($result->hasPrevious());
        self::assertTrue($result->hasNext());
        self::assertSame(26, $result->firstResult());
        self::assertSame(50, $result->lastResult());
    }

    public function testFirstPage(): void
    {
        $result = new PaginationResult(items: [], page: 1, pages: 3, total: 60, perPage: 25);

        self::assertFalse($result->hasPrevious());
        self::assertTrue($result->hasNext());
        self::assertSame(1, $result->firstResult());
        self::assertSame(25, $result->lastResult());
    }

    public function testLastPage(): void
    {
        $result = new PaginationResult(items: [], page: 3, pages: 3, total: 60, perPage: 25);

        self::assertTrue($result->hasPrevious());
        self::assertFalse($result->hasNext());
        self::assertSame(60, $result->lastResult());
    }

    public function testEmptyResultHasNoFirstRow(): void
    {
        $result = new PaginationResult(items: [], page: 1, pages: 1, total: 0, perPage: 25);

        self::assertFalse($result->hasPrevious());
        self::assertFalse($result->hasNext());
        self::assertSame(0, $result->firstResult());
        self::assertSame(0, $result->lastResult());
    }
}
