<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Search\SearchHit;

final class SearchHitTest extends TestCase
{
    public function testStoresFields(): void
    {
        $h = new SearchHit(
            id: 4711,
            score: 8.42,
            document: ['title' => 'Tea cup', 'storage_location_code' => 'WH1-A-03'],
        );

        self::assertSame(4711, $h->id);
        self::assertSame(8.42, $h->score);
        self::assertSame('Tea cup', $h->document['title']);
    }

    public function testRejectsNonPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchHit(id: 0, score: 1.0, document: []);
    }

    public function testRejectsNegativeScore(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SearchHit(id: 1, score: -0.1, document: []);
    }

    public function testZeroScoreIsAllowed(): void
    {
        // BM25 can legitimately return 0 for a barely-matching doc.
        $h = new SearchHit(id: 1, score: 0.0, document: []);

        self::assertSame(0.0, $h->score);
    }
}
