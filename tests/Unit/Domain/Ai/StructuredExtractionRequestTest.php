<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\StructuredExtractionRequest;

final class StructuredExtractionRequestTest extends TestCase
{
    public function testStoresFields(): void
    {
        $schema = ['type' => 'object', 'properties' => ['title' => ['type' => 'string']]];
        $r      = new StructuredExtractionRequest(
            instruction: 'Extract the book metadata.',
            sourceText: 'ISBN 9784123456789',
            jsonSchema: $schema,
        );

        self::assertSame($schema, $r->jsonSchema);
        self::assertSame('Extract the book metadata.', $r->instruction);
        self::assertNull($r->imageBytes);
    }

    public function testAcceptsImageOnlyInput(): void
    {
        $r = new StructuredExtractionRequest(
            instruction: 'Describe',
            sourceText: '',
            jsonSchema: ['type' => 'object'],
            imageBytes: "\xff\xd8\xff",
            imageMimeType: 'image/jpeg',
        );

        self::assertSame("\xff\xd8\xff", $r->imageBytes);
    }

    public function testRejectsEmptyInstruction(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StructuredExtractionRequest(
            instruction: '',
            sourceText: 'x',
            jsonSchema: ['type' => 'object'],
        );
    }

    public function testRejectsEmptySourceAndImage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StructuredExtractionRequest(
            instruction: 'i',
            sourceText: '',
            jsonSchema: ['type' => 'object'],
        );
    }

    public function testRejectsEmptySchema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StructuredExtractionRequest(
            instruction: 'i',
            sourceText: 'x',
            jsonSchema: [],
        );
    }
}
