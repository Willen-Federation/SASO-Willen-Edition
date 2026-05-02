<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode;

/**
 * Lifecycle of a row in `barcode_pool`.
 *
 *   Pending → Linked  (operator scanned the printed label and attached an item)
 *   Pending → Voided  (label was damaged or never used)
 *
 * Both Linked and Voided are terminal — the application does not transition
 * back. Re-issuing a label means minting a new code in a new batch.
 */
enum BarcodeStatus: string
{
    case Pending = 'pending';
    case Linked  = 'linked';
    case Voided  = 'voided';
}
