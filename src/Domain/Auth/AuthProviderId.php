<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

use InvalidArgumentException;

/**
 * Stable identifier for a registered {@see AuthProvider}.
 *
 * Stored as `auth_provider.id` in the database (assigned at row insert
 * via auto-increment in M4). Wrapped in a value object here so domain
 * code does not pass raw integers between layers — IDs from different
 * tables look identical and are easy to mix up.
 */
final readonly class AuthProviderId
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidArgumentException('AuthProviderId must be a positive integer.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function asString(): string
    {
        return (string) $this->value;
    }
}
