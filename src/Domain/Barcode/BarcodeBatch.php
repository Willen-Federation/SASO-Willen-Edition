<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode;

use DateTimeImmutable;

/**
 * Aggregate root for one row of `barcode_batch`.
 *
 * Pure read model — batches are immutable after creation; the only field
 * that changes is `createdCount` (set once when the mint service
 * materialises rows in `barcode_pool`).
 */
final readonly class BarcodeBatch
{
    public function __construct(
        public int $id,
        public string $code,
        public ?int $labelSheetLayoutId,
        public int $requestedCount,
        public int $createdCount,
        public ?string $createdBy,
        public BarcodeBatchOrigin $createdVia,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($requestedCount < 1) {
            throw new \InvalidArgumentException('BarcodeBatch.requestedCount must be at least 1.');
        }
        if ($createdCount < 0 || $createdCount > $requestedCount) {
            throw new \InvalidArgumentException('BarcodeBatch.createdCount must be between 0 and requestedCount.');
        }
    }
}
