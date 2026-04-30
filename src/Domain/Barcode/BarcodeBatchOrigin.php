<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode;

/**
 * Provenance of a barcode_batch row — recorded so audit screens can show
 * who produced a given pending sheet.
 */
enum BarcodeBatchOrigin: string
{
    case Web = 'web';
    case Mcp = 'mcp';
}
