<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Item\Attribute;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Item\Attribute\AttributeCode;
use Saso\Domain\Item\Attribute\AttributeDefinition;
use Saso\Domain\Item\Attribute\AttributeValueType;

final class AttributeDefinitionTest extends TestCase
{
    public function testStoresFields(): void
    {
        $d = $this->make(
            type: AttributeValueType::Int,
            unit: 'g',
        );

        self::assertSame(AttributeValueType::Int, $d->valueType);
        self::assertSame('g', $d->unit);
    }

    public function testEnumTypeRequiresValueList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty list when valueType = enum');

        $this->make(type: AttributeValueType::Enum, enumValues: null);
    }

    public function testEnumTypeRejectsEmptyValueList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(type: AttributeValueType::Enum, enumValues: []);
    }

    public function testNonEnumTypeMustNotCarryValueList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be null when valueType is not enum');

        $this->make(type: AttributeValueType::String, enumValues: ['stray']);
    }

    public function testEnumTypeAcceptsValueList(): void
    {
        $d = $this->make(type: AttributeValueType::Enum, enumValues: ['S', 'M', 'L']);

        self::assertSame(['S', 'M', 'L'], $d->enumValues);
    }

    public function testRejectsInvalidRegex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a valid PCRE pattern');

        $this->make(regex: '[unclosed');
    }

    public function testValidRegexIsAccepted(): void
    {
        $d = $this->make(regex: '^\\d{13}$');

        self::assertSame('^\\d{13}$', $d->validationRegex);
    }

    public function testRejectsNonPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(id: 0);
    }

    public function testRejectsEmptyLabels(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(labelEn: '');
    }

    public function testRejectsNegativeSortOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(sortOrder: -1);
    }

    public function testLocalisedLabel(): void
    {
        $d = $this->make(labelEn: 'Size', labelJa: 'サイズ');

        self::assertSame('Size', $d->localisedLabel('en'));
        self::assertSame('サイズ', $d->localisedLabel('ja'));
        self::assertSame('Size', $d->localisedLabel('fr'));   // unknown locale → English
    }

    /**
     * @param list<string>|null $enumValues
     */
    private function make(
        int $id = 1,
        string $labelEn = 'Size',
        string $labelJa = 'サイズ',
        AttributeValueType $type = AttributeValueType::String,
        ?string $unit = null,
        ?array $enumValues = null,
        ?string $regex = null,
        int $sortOrder = 0,
    ): AttributeDefinition {
        $now = new DateTimeImmutable('2026-04-27 10:00:00');

        return new AttributeDefinition(
            id: $id,
            code: new AttributeCode('size'),
            labelEn: $labelEn,
            labelJa: $labelJa,
            valueType: $type,
            unit: $unit,
            required: false,
            enumValues: $enumValues,
            validationRegex: $regex,
            sortOrder: $sortOrder,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
