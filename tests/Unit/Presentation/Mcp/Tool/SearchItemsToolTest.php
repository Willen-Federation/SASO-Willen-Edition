<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use PHPUnit\Framework\TestCase;
use Saso\Infrastructure\Search\NullSearchIndex;
use Saso\Presentation\Mcp\Tool\SearchItemsTool;

final class SearchItemsToolTest extends TestCase
{
    public function testNameAndMetadata(): void
    {
        $tool = new SearchItemsTool(new NullSearchIndex());

        self::assertSame('search_items', $tool->name());
        self::assertNotEmpty($tool->description());
        self::assertNull($tool->requiredScope());
    }

    public function testInputSchemaRequiresQuery(): void
    {
        $schema = (new SearchItemsTool(new NullSearchIndex()))->inputSchema();

        self::assertContains('query', $schema['required']);
    }

    public function testInvokeWithNullIndexReturnsEmpty(): void
    {
        $tool   = new SearchItemsTool(new NullSearchIndex());
        $result = $tool->invoke(['query' => 'item', 'limit' => 10], deviceId: 1);

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['total']);
    }

    public function testInvokeWithEmptyQueryReturnsEmpty(): void
    {
        $tool   = new SearchItemsTool(new NullSearchIndex());
        $result = $tool->invoke(['query' => '   '], deviceId: 1);

        self::assertSame([], $result['items']);
    }
}
