<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

/**
 * Outcome of one verification scan event.
 *
 * `Match`            — recorded location == scanned location, and the item
 *                      is within the session scope (or scope is global).
 * `Missing`          — expected within scope but never scanned. Computed at
 *                      `complete()` time, never on individual scans.
 * `Unexpected`       — scanned within scope but not in the expected set.
 * `MismatchLocation` — item exists in the expected set but at a different
 *                      location than the scan reports.
 * `UnknownCode`      — barcode could not be resolved to either pool row or
 *                      Feature triple.
 */
enum VerificationResult: string
{
    case Match            = 'match';
    case Missing          = 'missing';
    case Unexpected       = 'unexpected';
    case MismatchLocation = 'mismatch_location';
    case UnknownCode      = 'unknown_code';
}
