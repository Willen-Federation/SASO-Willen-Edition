<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode;

use InvalidArgumentException;

/**
 * Value object for a pending-barcode code.
 *
 * Format: `PND` + 9 zero-padded digits. The `PND` prefix lets a single
 * scan dispatcher distinguish a pending pool code from the legacy
 * 12-digit numeric `Feature::fullCode()` without a database lookup
 * fallthrough.
 */
final readonly class BarcodeCode
{
    public const PATTERN = '/^PND\d{9}$/';
    public const PREFIX  = 'PND';

    public function __construct(public string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'BarcodeCode must match %s; got "%s".',
                self::PATTERN,
                $value,
            ));
        }
    }

    public static function fromSequence(int $sequence): self
    {
        if ($sequence < 0 || $sequence > 999_999_999) {
            throw new InvalidArgumentException('BarcodeCode sequence must be in [0, 999_999_999].');
        }
        return new self(self::PREFIX.str_pad((string) $sequence, 9, '0', STR_PAD_LEFT));
    }

    public function asString(): string
    {
        return $this->value;
    }
}
