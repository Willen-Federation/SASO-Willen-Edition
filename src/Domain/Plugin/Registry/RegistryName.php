<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

use InvalidArgumentException;

/**
 * Identifier under which a plugin registers an entry in one of the
 * typed plugin registries (cf. ADR 0015).
 *
 * Format: 1-120 chars, lowercase alphanumeric + `_` + `-` + `:`.
 * Plugins choose names like `acme:custom-llm` for AI assistants or
 * `partner-app:saml` for auth providers — the prefix-by-vendor
 * convention prevents collisions between unrelated plugins.
 *
 * Reserved names (no prefix) are core SASO entries and refuse to be
 * replaced when a plugin attempts to register against them.
 */
final readonly class RegistryName
{
    public const MAX_LENGTH = 120;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('RegistryName must not be empty.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'RegistryName must be at most %d characters (got %d).',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match('/^[a-z0-9_:\-]+$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'RegistryName must be lowercase alphanumeric + "_" + "-" + ":" (got %s).',
                $value,
            ));
        }
    }

    /**
     * Returns whether this name is "reserved" — a core entry that
     * plugins cannot replace. By convention, names without a `:`
     * prefix are core; vendor-prefixed names (`acme:custom`) are
     * plugin-owned.
     */
    public function isReserved(): bool
    {
        return !str_contains($this->value, ':');
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
