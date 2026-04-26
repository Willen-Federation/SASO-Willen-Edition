<?php

declare(strict_types=1);

namespace Saso\Domain\Feature;

use InvalidArgumentException;

/**
 * Identifier for a `feature_flag` row, also the key call sites pass
 * to the OpenFeature client (cf. ADR 0005).
 *
 * Format: 1-120 chars, lower-case alphanumeric plus `.` and `_` —
 * mirrors the conventions established by major flag platforms
 * (LaunchDarkly, GrowthBook, ConfigCat) so flag names port cleanly.
 * Spaces, slashes, and hyphens are forbidden.
 */
final readonly class FeatureKey
{
    public const MAX_LENGTH = 120;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('FeatureKey must not be empty.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'FeatureKey must be at most %d characters (got %d).',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match('/^[a-z0-9._]+$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'FeatureKey must be lowercase alphanumeric plus "." and "_" (got %s).',
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
