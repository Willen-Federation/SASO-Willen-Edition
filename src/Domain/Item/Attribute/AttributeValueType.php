<?php

declare(strict_types=1);

namespace Saso\Domain\Item\Attribute;

/**
 * Drives the storage column + form widget for one attribute (cf.
 * ADR 0011).
 *
 * The `barcode` type is a specialised `string` — values are
 * additionally validated against EAN-13 / JAN / ISBN-13 / UPC-A
 * checksum rules at the application layer. The DB stores them in the
 * same column as plain strings.
 */
enum AttributeValueType: string
{
    case String      = 'string';
    case Int         = 'int';
    case Float       = 'float';
    case Bool        = 'bool';
    case Enum        = 'enum';
    case Barcode     = 'barcode';
    case MultiSelect = 'multi_select';
    case Tags        = 'tags';

    public function isNumeric(): bool
    {
        return $this === self::Int || $this === self::Float;
    }

    public function requiresEnumValues(): bool
    {
        return $this === self::Enum;
    }

    public function supportsMultipleValues(): bool
    {
        return $this === self::MultiSelect || $this === self::Tags;
    }
}
