<?php

declare(strict_types=1);

namespace Saso\Domain\ItemDraft;

final class ItemDraft
{
    /**
     * @param array<string, mixed>|null $userData
     * @param array<string, mixed>|null $aiResult
     */
    public function __construct(
        public readonly int $id,
        public readonly string $imagePath,
        public readonly ?string $barcodeHint,
        public readonly ?array $userData,
        public readonly ?array $aiResult,
        public readonly ItemDraftStatus $status,
        public readonly ?\DateTimeImmutable $processingStartedAt,
        public readonly ?string $errorDetail,
        public readonly ?int $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly bool $autoRegister = false,
        public readonly ?int $promotedItemId = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $userData = isset($row['user_data']) && $row['user_data'] !== null
            ? json_decode((string) $row['user_data'], associative: true)
            : null;
        $aiResult = isset($row['ai_result']) && $row['ai_result'] !== null
            ? json_decode((string) $row['ai_result'], associative: true)
            : null;

        return new self(
            id: (int) $row['id'],
            imagePath: (string) $row['image_path'],
            barcodeHint: isset($row['barcode_hint']) ? (string) $row['barcode_hint'] : null,
            userData: is_array($userData) ? $userData : null,
            aiResult: is_array($aiResult) ? $aiResult : null,
            status: ItemDraftStatus::from((string) $row['status']),
            processingStartedAt: isset($row['processing_started_at'])
                ? new \DateTimeImmutable((string) $row['processing_started_at'])
                : null,
            errorDetail: isset($row['error_detail']) ? (string) $row['error_detail'] : null,
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            updatedAt: new \DateTimeImmutable((string) $row['updated_at']),
            autoRegister: isset($row['auto_register']) && (int) $row['auto_register'] === 1,
            promotedItemId: isset($row['promoted_item_id']) && $row['promoted_item_id'] !== null
                ? (int) $row['promoted_item_id']
                : null,
        );
    }
}
