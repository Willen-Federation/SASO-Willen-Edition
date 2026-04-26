<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Search;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Search\SearchIndex;
use Saso\Domain\Search\SearchQuery;
use Saso\Domain\Search\SimilarityRequest;
use Saso\Infrastructure\Search\NullSearchIndex;

final class NullSearchIndexTest extends TestCase
{
    public function testImplementsSearchIndex(): void
    {
        self::assertInstanceOf(SearchIndex::class, new NullSearchIndex());
    }

    public function testSearchReturnsEmptyResult(): void
    {
        $index  = new NullSearchIndex();
        $result = $index->search(new SearchQuery(text: 'anything'));

        self::assertTrue($result->isEmpty());
        self::assertSame(0, $result->total);
    }

    public function testFindSimilarReturnsEmptyResult(): void
    {
        $index  = new NullSearchIndex();
        $result = $index->findSimilar(new SimilarityRequest(vector: [0.1, 0.2, 0.3]));

        self::assertTrue($result->isEmpty());
    }

    public function testUpsertIsNoOp(): void
    {
        (new NullSearchIndex())->upsert(1, ['title' => 'X']);
        self::assertTrue(true); // assertion required
    }

    public function testDeleteIsNoOp(): void
    {
        (new NullSearchIndex())->delete(1);
        self::assertTrue(true); // assertion required
    }
}
