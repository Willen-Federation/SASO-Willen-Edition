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
 *
 * `0` is reserved as a draft sentinel meaning "not yet persisted" — the
 * insert path on `PdoAuthProviderRepository` binds the value verbatim and
 * lets MySQL/SQLite auto-increment assign a real id. Any negative value
 * is a programming error.
 */
final readonly class AuthProviderId
{
    public function __construct(public int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('AuthProviderId must be a non-negative integer (0 = draft, >0 = persisted).');
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
