<?php

declare(strict_types=1);

namespace Saso\Domain\Item\Attribute;

use InvalidArgumentException;

/**
 * Canonical key for an `item_attribute_definition` row.
 *
 * Format: 1-120 chars, lowercase alphanumeric + `_` + `.`.
 * Mirrors the project-wide convention for stable identifiers
 * (`feature_flag.key_name`, `system_setting.key`) so attribute codes
 * land cleanly in OpenSearch field names and plugin extensions.
 */
final readonly class AttributeCode
{
    public const MAX_LENGTH = 120;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('AttributeCode must not be empty.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'AttributeCode must be at most %d characters (got %d).',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match('/^[a-z0-9_.]+$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'AttributeCode must be lowercase alphanumeric + "_" + "." (got %s).',
                $value,
            ));
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
