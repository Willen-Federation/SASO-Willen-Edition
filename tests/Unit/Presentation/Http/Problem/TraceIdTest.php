<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Http\Problem;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Http\Problem\TraceId;

final class TraceIdTest extends TestCase
{
    public function testFormatIsUuidv4(): void
    {
        $id = TraceId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id,
        );
    }

    public function testGeneratesUniqueValues(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; ++$i) {
            $ids[] = TraceId::generate();
        }

        self::assertCount(100, array_unique($ids));
    }
}
