<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Item\Attribute;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Item\Attribute\AttributeCode;
use Saso\Domain\Item\Attribute\AttributeDefinition;
use Saso\Domain\Item\Attribute\AttributeValueType;
use Saso\Infrastructure\Item\Attribute\PdoAttributeDefinitionRepository;

final class PdoAttributeDefinitionRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoAttributeDefinitionRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE item_attribute_definition (
                id               INTEGER PRIMARY KEY,
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

        $this->repo = new PdoAttributeDefinitionRepository($this->pdo);
    }

    public function testFindByCodeReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findByCode(new AttributeCode('nope')));
    }

    public function testSaveThenFindByCodeRoundTrips(): void
    {
        $d = $this->make(id: 1, code: 'size', type: AttributeValueType::String);
        $this->repo->save($d);

        $reread = $this->repo->findByCode(new AttributeCode('size'));
        self::assertNotNull($reread);
        self::assertSame('size', $reread->code->toString());
        self::assertSame(AttributeValueType::String, $reread->valueType);
    }

    public function testEnumValuesRoundTrip(): void
    {
        $d = $this->make(
            id: 1,
            code: 'size',
            type: AttributeValueType::Enum,
            enumValues: ['S', 'M', 'L', 'XL'],
        );
        $this->repo->save($d);

        $reread = $this->repo->findById(1);
        self::assertNotNull($reread);
        self::assertSame(['S', 'M', 'L', 'XL'], $reread->enumValues);
    }

    public function testSaveUpdatesExistingRow(): void
    {
        $d = $this->make(id: 1, code: 'size', labelEn: 'Old');
        $this->repo->save($d);

        $next = $this->make(id: 1, code: 'size', labelEn: 'New');
        $this->repo->save($next);

        $reread = $this->repo->findById(1);
        self::assertNotNull($reread);
        self::assertSame('New', $reread->labelEn);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM item_attribute_definition');
        self::assertInstanceOf(\PDOStatement::class, $stmt);
        self::assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testListOrderedSortedBySortThenCode(): void
    {
        $this->repo->save($this->make(id: 1, code: 'zebra', sortOrder: 10));
        $this->repo->save($this->make(id: 2, code: 'alpha', sortOrder: 5));
        $this->repo->save($this->make(id: 3, code: 'mid_a', sortOrder: 5));

        $codes = array_map(
            static fn (AttributeDefinition $d): string => $d->code->toString(),
            $this->repo->listOrdered(),
        );

        self::assertSame(['alpha', 'mid_a', 'zebra'], $codes);
    }

    public function testUniqueCodeIsEnforcedByDb(): void
    {
        $this->repo->save($this->make(id: 1, code: 'size'));

        $this->expectException(\PDOException::class);

        $this->repo->save($this->make(id: 2, code: 'size'));
    }

    public function testDeleteRemovesRow(): void
    {
        $this->repo->save($this->make(id: 1, code: 'size'));
        $this->repo->delete(1);

        self::assertNull($this->repo->findById(1));
    }

    /**
     * @param list<string>|null $enumValues
     */
    private function make(
        int $id,
        string $code,
        string $labelEn = 'Size',
        AttributeValueType $type = AttributeValueType::String,
        ?array $enumValues = null,
        int $sortOrder = 0,
    ): AttributeDefinition {
        $now = new DateTimeImmutable('2026-04-27 10:00:00');

        return new AttributeDefinition(
            id: $id,
            code: new AttributeCode($code),
            labelEn: $labelEn,
            labelJa: 'サイズ',
            valueType: $type,
            unit: null,
            required: false,
            enumValues: $enumValues,
            validationRegex: null,
            sortOrder: $sortOrder,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
