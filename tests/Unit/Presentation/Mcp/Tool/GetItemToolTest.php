<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Presentation\Mcp\Tool\GetItemTool;

final class GetItemToolTest extends TestCase
{
    private PDO $pdo;
    private GetItemTool $tool;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE item (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT NOT NULL,
                price       INTEGER,
                category_id INTEGER,
                jan_code    TEXT,
                stock       INTEGER,
                created_at  TEXT NOT NULL,
                updated_at  TEXT NOT NULL
            )',
        );
        $this->tool = new GetItemTool($this->pdo);
    }

    public function testInvalidIdReturnsNotFound(): void
    {
        $result = $this->tool->invoke(['id' => 0], 1);

        self::assertFalse($result['found']);
        self::assertNull($result['item']);
    }

    public function testMissingIdReturnsNotFound(): void
    {
        $result = $this->tool->invoke([], 1);

        self::assertFalse($result['found']);
        self::assertNull($result['item']);
    }

    public function testNotFoundIdReturnsNotFound(): void
    {
        $result = $this->tool->invoke(['id' => 999], 1);

        self::assertFalse($result['found']);
        self::assertNull($result['item']);
    }

    public function testFoundItemIsSerializedCorrectly(): void
    {
        $this->pdo->exec(
            "INSERT INTO item (name, price, category_id, jan_code, stock, created_at, updated_at)
             VALUES ('Widget', 500, 3, '1234567890123', 10, '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );
        $insertedId = (int) $this->pdo->lastInsertId();

        $result = $this->tool->invoke(['id' => $insertedId], 1);

        self::assertTrue($result['found']);
        $item = $result['item'];
        self::assertIsArray($item);
        self::assertSame($insertedId, $item['id']);
        self::assertSame('Widget', $item['name']);
        self::assertSame(500, $item['price']);
        self::assertSame(3, $item['categoryId']);
        self::assertSame('1234567890123', $item['janCode']);
        self::assertSame(10, $item['stock']);
    }

    public function testNullableFieldsHandledGracefully(): void
    {
        $this->pdo->exec(
            "INSERT INTO item (name, price, category_id, jan_code, stock, created_at, updated_at)
             VALUES ('Bare', NULL, NULL, NULL, NULL, '2024-01-01 00:00:00', '2024-01-01 00:00:00')",
        );
        $insertedId = (int) $this->pdo->lastInsertId();

        $result = $this->tool->invoke(['id' => $insertedId], 1);

        self::assertTrue($result['found']);
        $item = $result['item'];
        self::assertIsArray($item);
        self::assertNull($item['price']);
        self::assertNull($item['categoryId']);
        self::assertNull($item['janCode']);
        self::assertNull($item['stock']);
    }
}
