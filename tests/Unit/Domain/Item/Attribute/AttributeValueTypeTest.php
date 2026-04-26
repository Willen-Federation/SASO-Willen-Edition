<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Item\Attribute;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Item\Attribute\AttributeValueType;

final class AttributeValueTypeTest extends TestCase
{
    public function testEnumValuesMatchDatabaseEnum(): void
    {
        self::assertSame('string', AttributeValueType::String->value);
        self::assertSame('int', AttributeValueType::Int->value);
        self::assertSame('float', AttributeValueType::Float->value);
        self::assertSame('bool', AttributeValueType::Bool->value);
        self::assertSame('enum', AttributeValueType::Enum->value);
        self::assertSame('barcode', AttributeValueType::Barcode->value);
    }

    public function testIsNumeric(): void
    {
        self::assertTrue(AttributeValueType::Int->isNumeric());
        self::assertTrue(AttributeValueType::Float->isNumeric());
        self::assertFalse(AttributeValueType::String->isNumeric());
        self::assertFalse(AttributeValueType::Bool->isNumeric());
        self::assertFalse(AttributeValueType::Enum->isNumeric());
        self::assertFalse(AttributeValueType::Barcode->isNumeric());
    }

    public function testRequiresEnumValues(): void
    {
        self::assertTrue(AttributeValueType::Enum->requiresEnumValues());
        foreach ([
            AttributeValueType::String,
            AttributeValueType::Int,
            AttributeValueType::Float,
            AttributeValueType::Bool,
            AttributeValueType::Barcode,
        ] as $t) {
            self::assertFalse($t->requiresEnumValues(), $t->value);
        }
    }
}
