<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Presentation\Mcp\Tool\RegisterItemTool;

final class RegisterItemToolTest extends TestCase
{
    private PDO $pdo;
    private RegisterItemTool $tool;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE item (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                name        TEXT NOT NULL,
                price       INTEGER NOT NULL DEFAULT 0,
                category_id INTEGER NOT NULL,
                jan_code    TEXT,
                stock       INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT NOT NULL,
                updated_at  TEXT NOT NULL
            )',
        );
        $this->tool = new RegisterItemTool($this->pdo);
    }

    public function testMissingNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->tool->invoke(['categoryId' => 1], 1);
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->tool->invoke(['name' => '   ', 'categoryId' => 1], 1);
    }

    public function testMissingCategoryIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->tool->invoke(['name' => 'Widget'], 1);
    }

    public function testZeroCategoryIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->tool->invoke(['name' => 'Widget', 'categoryId' => 0], 1);
    }

    public function testSuccessfulInsertReturnsFields(): void
    {
        $result = $this->tool->invoke(['name' => 'Gadget', 'categoryId' => 2], 1);

        self::assertGreaterThan(0, $result['id']);
        self::assertSame('Gadget', $result['name']);
        self::assertSame(2, $result['categoryId']);
        self::assertSame(0, $result['price']);
        self::assertSame(0, $result['stock']);
        self::assertNotEmpty($result['createdAt']);
    }

    public function testJanCodeNullWhenEmpty(): void
    {
        $this->tool->invoke(['name' => 'Item', 'categoryId' => 1, 'janCode' => '  '], 1);

        $row = $this->pdo->query('SELECT jan_code FROM item LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertNull($row['jan_code']);
    }

    public function testJanCodeStoredWhenProvided(): void
    {
        $this->tool->invoke(['name' => 'Item', 'categoryId' => 1, 'janCode' => '9784003100011'], 1);

        $row = $this->pdo->query('SELECT jan_code FROM item LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertSame('9784003100011', $row['jan_code']);
    }

    public function testPriceAndStockDefaults(): void
    {
        $this->tool->invoke(['name' => 'Item', 'categoryId' => 1], 1);

        $row = $this->pdo->query('SELECT price, stock FROM item LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertSame('0', (string) $row['price']);
        self::assertSame('0', (string) $row['stock']);
    }
}
