<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode\Repository;

use Saso\Domain\Barcode\BarcodeBatch;
use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\BarcodeStatus;
use Saso\Domain\Barcode\PendingBarcode;

/**
 * Persistence contract for the pending-barcode pool + mint batches.
 *
 * Implementations enforce two invariants atomically:
 *   - `mintBatch()` creates the `barcode_batch` row AND `requestedCount`
 *     `barcode_pool` rows in a single transaction; on partial failure the
 *     entire batch is rolled back.
 *   - `save()` rejects writes that contradict the on-disk status (e.g.
 *     trying to relink an already-voided code).
 */
interface BarcodeRepository
{
    public function findByCode(BarcodeCode $code): ?PendingBarcode;

    public function findBatchById(int $batchId): ?BarcodeBatch;

    /**
     * Atomically creates a new batch and `requestedCount` pending pool rows.
     *
     * Implementations are responsible for unique-code generation and
     * collision retries. Returns the persisted aggregates so the caller
     * can immediately render the PDF without a second query.
     *
     * @return array{batch: BarcodeBatch, codes: list<PendingBarcode>}
     */
    public function mintBatch(
        int $requestedCount,
        ?int $labelSheetLayoutId,
        ?string $createdBy,
        \Saso\Domain\Barcode\BarcodeBatchOrigin $origin,
    ): array;

    public function save(PendingBarcode $barcode): void;

    /**
     * @return list<PendingBarcode>
     */
    public function listByStatus(BarcodeStatus $status, int $limit = 100, int $offset = 0): array;
}
