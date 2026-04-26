<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\EmbeddingResponse;

final class EmbeddingResponseTest extends TestCase
{
    public function testReportsDimensions(): void
    {
        $r = new EmbeddingResponse(
            vectors: [[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]],
            usage: new AiUsage(),
            model: 'embed-test',
        );

        self::assertSame(3, $r->dimensions());
        self::assertCount(2, $r->vectors);
    }

    public function testRejectsEmptyVectors(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EmbeddingResponse(vectors: [], usage: new AiUsage(), model: 'm');
    }

    public function testRejectsEmptyVectorRow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EmbeddingResponse(vectors: [[]], usage: new AiUsage(), model: 'm');
    }

    public function testRejectsRaggedVectors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('same dimension');

        new EmbeddingResponse(
            vectors: [[0.1, 0.2], [0.3, 0.4, 0.5]],
            usage: new AiUsage(),
            model: 'm',
        );
    }
}
