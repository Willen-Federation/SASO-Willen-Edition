<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Search\SearchQuery;

final class SearchQueryTest extends TestCase
{
    public function testStoresFields(): void
    {
        $q = new SearchQuery(
            text: 'red book',
            filters: ['category_path' => 'books/jp'],
            limit: 50,
            offset: 100,
            sort: SearchQuery::SORT_NEWEST,
        );

        self::assertSame('red book', $q->text);
        self::assertSame(['category_path' => 'books/jp'], $q->filters);
        self::assertSame(50, $q->limit);
        self::assertSame(100, $q->offset);
        self::assertSame('newest', $q->sort);
    }

    public function testDefaultsAreSensible(): void
    {
        $q = new SearchQuery(text: 'x');

        self::assertSame(SearchQuery::DEFAULT_LIMIT, $q->limit);
        self::assertSame(0, $q->offset);
        self::assertSame(SearchQuery::SORT_RELEVANCE, $q->sort);
        self::assertSame([], $q->filters);
    }

    public function testRejectsEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchQuery(text: '');
    }

    public function testRejectsZeroLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchQuery(text: 'x', limit: 0);
    }

    public function testRejectsLimitAboveCeiling(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchQuery(text: 'x', limit: SearchQuery::MAX_LIMIT + 1);
    }

    public function testRejectsNegativeOffset(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchQuery(text: 'x', offset: -1);
    }

    public function testRejectsUnknownSort(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchQuery(text: 'x', sort: 'random');
    }
}
