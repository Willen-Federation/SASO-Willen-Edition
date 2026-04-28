<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Barcode;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Barcode\BarcodeBatch;
use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\BarcodeStatus;
use Saso\Domain\Barcode\PendingBarcode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;

/**
 * PDO-backed {@see BarcodeRepository}.
 *
 * `mintBatch()` runs inside an explicit transaction:
 *   1. Insert `barcode_batch` (initially `created_count = 0`).
 *   2. Loop `requestedCount` times: generate a 9-digit sequence via
 *      `random_int(0, 999_999_999)`, format as `PND` + zero-padded.
 *      INSERT IGNORE absorbs the once-in-a-billion collision; on every
 *      ignored insert we retry up to 3× before giving up on the batch.
 *   3. UPDATE `barcode_batch.created_count` to the actual number of rows
 *      successfully inserted (always == requestedCount in practice;
 *      never less unless an unrecoverable collision occurs).
 *
 * No background workers — the entire batch is persisted before the call
 * returns so the PDF renderer can iterate the codes from memory.
 */
final class PdoBarcodeRepository implements BarcodeRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findByCode(BarcodeCode $code): ?PendingBarcode
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, status, batch_id, linked_item_id, linked_at, '
            .' linked_by_device_id, voided_at, void_reason, created_at'
            .' FROM barcode_pool WHERE code = :code LIMIT 1'
        );
        $stmt->bindValue('code', $code->asString());
        $stmt->execute();
        /** @var array<string, scalar|null>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    public function findBatchById(int $batchId): ?BarcodeBatch
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, label_sheet_layout_id, requested_count, created_count, '
            .' created_by, created_via, created_at, updated_at'
            .' FROM barcode_batch WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue('id', $batchId, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, scalar|null>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrateBatch($row);
    }

    public function mintBatch(
        int $requestedCount,
        ?int $labelSheetLayoutId,
        ?string $createdBy,
        BarcodeBatchOrigin $origin,
    ): array {
        if ($requestedCount < 1 || $requestedCount > 5_000) {
            throw new \InvalidArgumentException('mintBatch count must be in [1, 5000].');
        }

        $now    = new DateTimeImmutable('now', $this->timezone);
        $nowSql = $now->format('Y-m-d H:i:s');
        $slug   = sprintf('PND-%s-%04d', $now->format('Ymd'), random_int(0, 9999));

        $this->pdo->beginTransaction();
        try {
            $insertBatch = $this->pdo->prepare(
                'INSERT INTO barcode_batch '
                .'(code, label_sheet_layout_id, requested_count, created_count, '
                .' created_by, created_via, created_at, updated_at) '
                .'VALUES (:code, :layout, :requested, 0, :by, :via, :ca, :ua)'
            );
            $insertBatch->bindValue('code', $slug);
            $insertBatch->bindValue('layout', $labelSheetLayoutId, $labelSheetLayoutId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insertBatch->bindValue('requested', $requestedCount, PDO::PARAM_INT);
            $insertBatch->bindValue('by', $createdBy, $createdBy === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $insertBatch->bindValue('via', $origin->value);
            $insertBatch->bindValue('ca', $nowSql);
            $insertBatch->bindValue('ua', $nowSql);
            $insertBatch->execute();
            $batchId = (int) $this->pdo->lastInsertId();

            $insertCode = $this->pdo->prepare(
                'INSERT INTO barcode_pool (code, status, batch_id, created_at) '
                .'VALUES (:code, :status, :batch, :ca)'
            );

            $codes  = [];
            $tries  = 0;
            $maxTry = max(10, $requestedCount * 3);
            while (count($codes) < $requestedCount && $tries < $maxTry) {
                $tries++;
                $code = BarcodeCode::fromSequence(random_int(0, 999_999_999));
                try {
                    $insertCode->bindValue('code', $code->asString());
                    $insertCode->bindValue('status', BarcodeStatus::Pending->value);
                    $insertCode->bindValue('batch', $batchId, PDO::PARAM_INT);
                    $insertCode->bindValue('ca', $nowSql);
                    $insertCode->execute();
                    $codes[] = new PendingBarcode(
                        id:               (int) $this->pdo->lastInsertId(),
                        code:             $code,
                        status:           BarcodeStatus::Pending,
                        batchId:          $batchId,
                        linkedItemId:     null,
                        linkedAt:         null,
                        linkedByDeviceId: null,
                        voidedAt:         null,
                        voidReason:       null,
                        createdAt:        $now,
                    );
                } catch (\PDOException $e) {
                    // Unique constraint collision — retry with a fresh code.
                    if (str_contains($e->getMessage(), 'uniq_barcode_pool_code')
                        || str_contains($e->getMessage(), 'Duplicate')
                        || str_contains($e->getMessage(), 'UNIQUE')) {
                        continue;
                    }
                    throw $e;
                }
            }
            if (count($codes) < $requestedCount) {
                throw new \RuntimeException(sprintf(
                    'mintBatch could not produce %d unique codes after %d attempts.',
                    $requestedCount,
                    $tries,
                ));
            }

            $update = $this->pdo->prepare(
                'UPDATE barcode_batch SET created_count = :n, updated_at = :ua WHERE id = :id'
            );
            $update->bindValue('n', $requestedCount, PDO::PARAM_INT);
            $update->bindValue('ua', $nowSql);
            $update->bindValue('id', $batchId, PDO::PARAM_INT);
            $update->execute();

            $this->pdo->commit();

            $batch = new BarcodeBatch(
                id:                  $batchId,
                code:                $slug,
                labelSheetLayoutId:  $labelSheetLayoutId,
                requestedCount:      $requestedCount,
                createdCount:        $requestedCount,
                createdBy:           $createdBy,
                createdVia:          $origin,
                createdAt:           $now,
                updatedAt:           $now,
            );
            return ['batch' => $batch, 'codes' => $codes];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function save(PendingBarcode $barcode): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE barcode_pool SET '
            .' status = :status, linked_item_id = :item, linked_at = :linkedAt, '
            .' linked_by_device_id = :device, voided_at = :voidedAt, void_reason = :reason '
            .' WHERE id = :id'
        );
        $stmt->bindValue('status',   $barcode->status->value);
        $stmt->bindValue('item',     $barcode->linkedItemId, $barcode->linkedItemId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('linkedAt', $barcode->linkedAt?->format('Y-m-d H:i:s'), $barcode->linkedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('device',   $barcode->linkedByDeviceId, $barcode->linkedByDeviceId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('voidedAt', $barcode->voidedAt?->format('Y-m-d H:i:s'), $barcode->voidedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('reason',   $barcode->voidReason, $barcode->voidReason === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue('id',       $barcode->id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function listByStatus(BarcodeStatus $status, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, code, status, batch_id, linked_item_id, linked_at, '
            .' linked_by_device_id, voided_at, void_reason, created_at'
            .' FROM barcode_pool WHERE status = :status'
            .' ORDER BY created_at DESC, id DESC'
            .' LIMIT :lim OFFSET :off'
        );
        $stmt->bindValue('status', $status->value);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        /** @var list<array<string, scalar|null>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->hydrate($row);
        }
        return $out;
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function hydrate(array $row): PendingBarcode
    {
        return new PendingBarcode(
            id:               (int) $row['id'],
            code:             new BarcodeCode((string) $row['code']),
            status:           BarcodeStatus::from((string) $row['status']),
            batchId:          (int) $row['batch_id'],
            linkedItemId:     isset($row['linked_item_id']) ? (string) $row['linked_item_id'] : null,
            linkedAt:         isset($row['linked_at']) ? new DateTimeImmutable((string) $row['linked_at'], $this->timezone) : null,
            linkedByDeviceId: isset($row['linked_by_device_id']) ? (int) $row['linked_by_device_id'] : null,
            voidedAt:         isset($row['voided_at']) ? new DateTimeImmutable((string) $row['voided_at'], $this->timezone) : null,
            voidReason:       isset($row['void_reason']) ? (string) $row['void_reason'] : null,
            createdAt:        new DateTimeImmutable((string) $row['created_at'], $this->timezone),
        );
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function hydrateBatch(array $row): BarcodeBatch
    {
        return new BarcodeBatch(
            id:                 (int) $row['id'],
            code:               (string) $row['code'],
            labelSheetLayoutId: isset($row['label_sheet_layout_id']) ? (int) $row['label_sheet_layout_id'] : null,
            requestedCount:     (int) $row['requested_count'],
            createdCount:       (int) $row['created_count'],
            createdBy:          isset($row['created_by']) ? (string) $row['created_by'] : null,
            createdVia:         BarcodeBatchOrigin::from((string) $row['created_via']),
            createdAt:          new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt:          new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }
}
