<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\EmbeddingTask;

final class EmbeddingRequestTest extends TestCase
{
    public function testStoresTextInputs(): void
    {
        $r = new EmbeddingRequest(
            textInputs: ['hello', 'world'],
            task: EmbeddingTask::RetrievalQuery,
        );

        self::assertSame(['hello', 'world'], $r->textInputs);
        self::assertSame(EmbeddingTask::RetrievalQuery, $r->task);
    }

    public function testStoresImageInputs(): void
    {
        $r = new EmbeddingRequest(imageInputs: ["\xff\xd8\xff"]);

        self::assertCount(1, $r->imageInputs);
    }

    public function testRejectsAllEmptyInputs(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EmbeddingRequest();
    }

    public function testRejectsBelowMinimumDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EmbeddingRequest(textInputs: ['x'], dimensions: 8);
    }

    public function testTaskEnumValues(): void
    {
        self::assertSame('retrieval.query', EmbeddingTask::RetrievalQuery->value);
        self::assertSame('retrieval.passage', EmbeddingTask::RetrievalPassage->value);
        self::assertSame('similarity', EmbeddingTask::Similarity->value);
    }
}
