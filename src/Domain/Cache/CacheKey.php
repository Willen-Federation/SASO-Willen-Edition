<?php

declare(strict_types=1);

namespace Saso\Domain\Cache;

use InvalidArgumentException;

/**
 * Identifier for a {@see Cache} entry (cf. ADR 0012).
 *
 * Format: 1-200 chars, alphanumeric + `:` + `.` + `_` + `-`.
 * The colon convention follows Redis idiom (`feature_flag:42` ↔
 * the `feature_flag` table row 42); other adapters (in-memory test
 * fakes, file-backed shared-hosting fallback) inherit the same shape
 * so a key written through one adapter is portable to another.
 *
 * Spaces, slashes, control characters are rejected — keys must be
 * safe to log, embed in URLs, and pipe through to Redis without
 * quoting.
 */
final readonly class CacheKey
{
    public const MAX_LENGTH = 200;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('CacheKey must not be empty.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'CacheKey must be at most %d characters (got %d).',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match('/^[A-Za-z0-9:._\-]+$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'CacheKey must be alphanumeric + ":" + "." + "_" + "-" (got %s).',
                $value,
            ));
        }
    }

    /**
     * Composes a key from canonical parts joined with `:`. Each part
     * is normalised (trimmed, leading-/trailing-colon stripped) and
     * empty parts are rejected.
     */
    public static function fromParts(string ...$parts): self
    {
        if ($parts === []) {
            throw new InvalidArgumentException('CacheKey requires at least one part.');
        }
        $normalised = array_map(static function (string $part): string {
            $trimmed = trim($part, ": \t\n\r\0\x0B");
            if ($trimmed === '') {
                throw new InvalidArgumentException('CacheKey parts must not be empty.');
            }

            return $trimmed;
        }, $parts);

        return new self(implode(':', $normalised));
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
