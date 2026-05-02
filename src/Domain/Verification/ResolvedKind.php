<?php

declare(strict_types=1);

namespace Saso\Domain\Verification;

/**
 * What the BarcodeRouterService reported for the scanned code.
 *
 * `Pending` — the scan resolved to a row in `barcode_pool` (label printed
 *             but never linked to an item).
 * `Feature` — the scan resolved to a parsed `Item.id + Color + Size` triple
 *             (legacy 12-digit Feature.fullCode).
 * `Unknown` — neither lookup matched.
 */
enum ResolvedKind: string
{
    case Pending = 'pending';
    case Feature = 'feature';
    case Unknown = 'unknown';
}
