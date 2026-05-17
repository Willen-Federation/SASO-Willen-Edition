<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode;

use InvalidArgumentException;

/**
 * Value object for a pending-barcode code.
 *
 * Format: either an uppercase alphanumeric prefix followed by digits, or a
 * 13-digit JAN/EAN code. 12-digit numeric legacy `Feature::fullCode()` values
 * stay excluded so the scan dispatcher can keep routing those without a
 * database lookup fallthrough.
 */
final readonly class BarcodeCode
{
    public const PATTERN = '/^(?:[A-Z][A-Z0-9]{0,7}\d{4,12}|\d{13})$/';
    public const PREFIX  = 'PND';
    public const JAN_PREFIX = '49';

    public string $value;

    public function __construct(string $value)
    {
        $value = strtoupper($value);
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'BarcodeCode must match %s; got "%s".',
                self::PATTERN,
                $value,
            ));
        }
        $this->value = $value;
    }

    public static function fromSequence(int $sequence, string $prefix = self::PREFIX, int $width = 9): self
    {
        if ($sequence < 0 || $sequence > 999_999_999_999) {
            throw new InvalidArgumentException('BarcodeCode sequence must be in [0, 999_999_999_999].');
        }
        $prefix = self::normalizePrefix($prefix);
        $width = max(4, min(12, $width));
        return new self($prefix.str_pad((string) $sequence, $width, '0', STR_PAD_LEFT));
    }

    public static function fromJanSequence(int $sequence, string $prefix = self::JAN_PREFIX): self
    {
        if ($sequence < 0 || $sequence > 999_999_999_999) {
            throw new InvalidArgumentException('JAN sequence must be in [0, 999_999_999_999].');
        }

        $prefix = self::normalizeJanPrefix($prefix);
        $sequenceWidth = 12 - strlen($prefix);
        $body = $prefix.str_pad((string) $sequence, $sequenceWidth, '0', STR_PAD_LEFT);

        return new self($body.self::ean13CheckDigit($body));
    }

    public static function normalizePrefix(string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $prefix) ?? '');
        if ($prefix === '' || preg_match('/^[A-Z]/', $prefix) !== 1) {
            $prefix = self::PREFIX;
        }
        return substr($prefix, 0, 8);
    }

    public static function normalizeJanPrefix(string $prefix): string
    {
        $prefix = preg_replace('/\D+/', '', $prefix) ?? '';
        if ($prefix === '') {
            $prefix = self::JAN_PREFIX;
        }
        return substr($prefix, 0, 11);
    }

    public static function ean13CheckDigit(string $body): int
    {
        if (preg_match('/^\d{12}$/', $body) !== 1) {
            throw new InvalidArgumentException('EAN-13 check digit body must be 12 digits.');
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $body[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    public function asString(): string
    {
        return $this->value;
    }
}
