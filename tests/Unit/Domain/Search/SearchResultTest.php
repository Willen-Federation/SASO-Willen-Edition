<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Search\SearchHit;
use Saso\Domain\Search\SearchResult;

final class SearchResultTest extends TestCase
{
    public function testEmpty(): void
    {
        $r = SearchResult::empty();

        self::assertTrue($r->isEmpty());
        self::assertSame([], $r->hits);
        self::assertSame(0, $r->total);
        self::assertSame(0, $r->tookMs);
    }

    public function testWithHits(): void
    {
        $r = new SearchResult(
            hits: [
                new SearchHit(id: 1, score: 9.0, document: ['title' => 'A']),
                new SearchHit(id: 2, score: 5.0, document: ['title' => 'B']),
            ],
            total: 100,
            tookMs: 12,
        );

        self::assertFalse($r->isEmpty());
        self::assertCount(2, $r->hits);
        self::assertSame(100, $r->total);
        self::assertSame(12, $r->tookMs);
    }

    public function testRejectsNegativeTotal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchResult(hits: [], total: -1);
    }

    public function testRejectsNegativeTookMs(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchResult(hits: [], total: 0, tookMs: -1);
    }

    public function testRejectsHitsExceedingTotal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed total');

        new SearchResult(
            hits: [new SearchHit(id: 1, score: 1.0, document: [])],
            total: 0,
        );
    }
}
