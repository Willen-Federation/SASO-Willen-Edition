<?php

declare(strict_types=1);

namespace Saso\Domain\Barcode;

use DateTimeImmutable;
use DomainException;

/**
 * Aggregate root for one row of `barcode_pool`.
 *
 * `link()` and `void()` produce new instances rather than mutating in
 * place — repository implementations persist the returned aggregate.
 * Both transitions are guarded by {@see BarcodeStatus} (Pending is the
 * only valid source).
 */
final readonly class PendingBarcode
{
    public function __construct(
        public int $id,
        public BarcodeCode $code,
        public BarcodeStatus $status,
        public int $batchId,
        public ?string $linkedItemId,
        public ?DateTimeImmutable $linkedAt,
        public ?int $linkedByDeviceId,
        public ?DateTimeImmutable $voidedAt,
        public ?string $voidReason,
        public DateTimeImmutable $createdAt,
    ) {
        if ($id < 1) {
            throw new \InvalidArgumentException('PendingBarcode.id must be a positive integer.');
        }
        if ($batchId < 1) {
            throw new \InvalidArgumentException('PendingBarcode.batchId must be a positive integer.');
        }
        // Linked → must have linked_item_id + linked_at
        if ($status === BarcodeStatus::Linked && ($linkedItemId === null || $linkedItemId === '' || $linkedAt === null)) {
            throw new \InvalidArgumentException('Linked barcode must carry linkedItemId and linkedAt.');
        }
        // Voided → must have voided_at
        if ($status === BarcodeStatus::Voided && $voidedAt === null) {
            throw new \InvalidArgumentException('Voided barcode must carry voidedAt.');
        }
    }

    public function isAvailable(): bool
    {
        return $this->status === BarcodeStatus::Pending;
    }

    public function link(string $itemId, DateTimeImmutable $at, ?int $deviceId = null): self
    {
        if (!$this->isAvailable()) {
            throw new DomainException(sprintf(
                'Barcode %s cannot be linked: status is %s.',
                $this->code->asString(),
                $this->status->value,
            ));
        }
        return new self(
            id:                $this->id,
            code:              $this->code,
            status:            BarcodeStatus::Linked,
            batchId:           $this->batchId,
            linkedItemId:      $itemId,
            linkedAt:          $at,
            linkedByDeviceId:  $deviceId,
            voidedAt:          null,
            voidReason:        null,
            createdAt:         $this->createdAt,
        );
    }

    public function void(string $reason, DateTimeImmutable $at): self
    {
        if (!$this->isAvailable()) {
            throw new DomainException(sprintf(
                'Barcode %s cannot be voided: status is %s.',
                $this->code->asString(),
                $this->status->value,
            ));
        }
        return new self(
            id:                $this->id,
            code:              $this->code,
            status:            BarcodeStatus::Voided,
            batchId:           $this->batchId,
            linkedItemId:      null,
            linkedAt:          null,
            linkedByDeviceId:  null,
            voidedAt:          $at,
            voidReason:        $reason,
            createdAt:         $this->createdAt,
        );
    }
}
