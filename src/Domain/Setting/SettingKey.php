<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

use InvalidArgumentException;

/**
 * Identifier for a `system_setting` row.
 *
 * Format constraints (1-120 chars, alphanumeric + `.`, `_`, `-`) keep
 * keys safe to embed in URLs (admin UI), translation lookups
 * (`error.<code>` style), and structured log fields. Spaces and slashes
 * are forbidden so a key never accidentally collides with a path
 * segment or has to be quoted in a query parameter.
 */
final readonly class SettingKey
{
    public const MAX_LENGTH = 120;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('SettingKey must not be empty.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'SettingKey must be at most %d characters (got %d).',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match('/^[A-Za-z0-9_.\-]+$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'SettingKey contains illegal characters: %s',
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
