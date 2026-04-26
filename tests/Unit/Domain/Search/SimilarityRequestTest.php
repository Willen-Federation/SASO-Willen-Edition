<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Search\SimilarityRequest;

final class SimilarityRequestTest extends TestCase
{
    public function testStoresFields(): void
    {
        $r = new SimilarityRequest(
            vector: [0.1, 0.2, 0.3, 0.4],
            mode: SimilarityRequest::MODE_IMAGE,
            k: 25,
            filters: ['storage_location_code' => 'WH1-A-03'],
        );

        self::assertSame([0.1, 0.2, 0.3, 0.4], $r->vector);
        self::assertSame('image', $r->mode);
        self::assertSame(25, $r->k);
        self::assertSame(4, $r->dimensions());
    }

    public function testDefaultMode(): void
    {
        $r = new SimilarityRequest(vector: [0.1]);

        self::assertSame(SimilarityRequest::MODE_TEXT, $r->mode);
        self::assertSame(SimilarityRequest::DEFAULT_K, $r->k);
    }

    public function testRejectsEmptyVector(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SimilarityRequest(vector: []);
    }

    public function testRejectsUnknownMode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SimilarityRequest(vector: [0.1], mode: 'audio');
    }

    public function testRejectsZeroK(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SimilarityRequest(vector: [0.1], k: 0);
    }

    public function testRejectsKAboveCeiling(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SimilarityRequest(vector: [0.1], k: SimilarityRequest::MAX_K + 1);
    }
}
