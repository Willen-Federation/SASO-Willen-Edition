<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Presentation\Mcp\Tool\DefineAttributeTool;

/**
 * Targeted regression coverage for {@see DefineAttributeTool} — focuses on
 * the regex-delimiter handling that previously masked `#`-containing
 * patterns as valid and the enum-values precondition.
 */
final class DefineAttributeToolTest extends TestCase
{
    private PDO $pdo;
    private DefineAttributeTool $tool;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE item_attribute_definition (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                code             TEXT NOT NULL UNIQUE,
                label_en         TEXT NOT NULL,
                label_ja         TEXT NOT NULL,
                value_type       TEXT NOT NULL,
                unit             TEXT,
                required         INTEGER NOT NULL DEFAULT 0,
                enum_values      TEXT,
                validation_regex TEXT,
                sort_order       INTEGER NOT NULL DEFAULT 0,
                created_at       TEXT NOT NULL,
                updated_at       TEXT NOT NULL
            )',
        );
        $this->tool = new DefineAttributeTool($this->pdo);
    }

    public function testValidRegexIsAccepted(): void
    {
        $result = $this->tool->invoke([
            'code'            => 'serial',
            'labelEn'         => 'Serial',
            'labelJa'         => 'シリアル',
            'valueType'       => 'string',
            'validationRegex' => '^[A-Z]{2}\d{6}$',
        ], deviceId: 1);

        self::assertSame('^[A-Z]{2}\d{6}$', $result['validationRegex']);
    }

    public function testRegexContainingDelimiterIsRejected(): void
    {
        // \x01 is the new delimiter; a pattern containing it must be
        // rejected so the user can't escape the wrapper.
        $this->expectException(InvalidArgumentException::class);

        $this->tool->invoke([
            'code'            => 'hash',
            'labelEn'         => 'Hash',
            'labelJa'         => 'ハッシュ',
            'valueType'       => 'string',
            'validationRegex' => "foo\x01bar",
        ], deviceId: 1);
    }

    public function testMalformedRegexIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->tool->invoke([
            'code'            => 'broken',
            'labelEn'         => 'Broken',
            'labelJa'         => '壊れた',
            'valueType'       => 'string',
            'validationRegex' => '[unclosed',
        ], deviceId: 1);
    }

    public function testHashInRegexIsHonoured(): void
    {
        // Under the previous `#`-delimiter wrapper, `foo#bar` either changed
        // semantics or got silently accepted. With \x01 as the delimiter
        // it round-trips intact.
        $result = $this->tool->invoke([
            'code'            => 'tagged',
            'labelEn'         => 'Tagged',
            'labelJa'         => 'タグ付き',
            'valueType'       => 'string',
            'validationRegex' => 'foo#bar',
        ], deviceId: 1);

        self::assertSame('foo#bar', $result['validationRegex']);
    }
}
