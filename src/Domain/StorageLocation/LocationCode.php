<?php

declare(strict_types=1);

namespace Saso\Domain\StorageLocation;

use InvalidArgumentException;

/**
 * Operator-readable, barcode-friendly identifier for a single
 * `storage_location` row (cf. ADR 0011).
 *
 * Format: 1-120 chars, uppercase alphanumeric + `-` segments. Lower
 * case and other punctuation are rejected — codes appear in barcodes
 * and on printed labels where ambiguity (`O` vs `0`, `I` vs `1`)
 * matters. Operators see them in CAPS, scanners read them as CAPS.
 *
 * The value object is immutable; the `from*` factories construct
 * canonical codes from typed parts (e.g.
 * `LocationCode::fromParts('WH1', 'A', '03', 'B12')` →
 * `WH1-A-03-B12`). Tree-aware code generation lives in
 * {@see LocationCodeGenerator} — a sibling helper added with the
 * admin UI in M6-E2.
 */
final readonly class LocationCode
{
    public const MAX_LENGTH = 120;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('LocationCode must not be empty.');
        }
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'LocationCode must be at most %d characters (got %d).',
                self::MAX_LENGTH,
                strlen($value),
            ));
        }
        if (preg_match('/^[A-Z0-9](?:[A-Z0-9]|-(?=[A-Z0-9]))*$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'LocationCode must be uppercase alphanumeric + "-" segments (got %s).',
                $value,
            ));
        }
    }

    /**
     * Composes a canonical code from segment parts. Each segment is
     * normalised to upper case; empty segments are rejected.
     */
    public static function fromParts(string ...$parts): self
    {
        if ($parts === []) {
            throw new InvalidArgumentException('LocationCode requires at least one segment.');
        }
        $normalised = array_map(static function (string $segment): string {
            $trimmed = trim($segment);
            if ($trimmed === '') {
                throw new InvalidArgumentException('LocationCode segments must not be empty.');
            }

            return strtoupper($trimmed);
        }, $parts);

        return new self(implode('-', $normalised));
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
