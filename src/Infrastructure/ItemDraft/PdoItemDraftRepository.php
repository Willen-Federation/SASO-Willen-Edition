<?php

declare(strict_types=1);

namespace Saso\Infrastructure\ItemDraft;

use Saso\Domain\ItemDraft\ItemDraft;
use Saso\Domain\ItemDraft\ItemDraftStatus;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;

final class PdoItemDraftRepository implements ItemDraftRepository
{
    public function __construct(
        private readonly \PDO $pdo,
    ) {
    }

    public function findById(int $id): ?ItemDraft
    {
        $stmt = $this->pdo->prepare('SELECT * FROM item_draft WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return ItemDraft::fromRow($row);
    }

    /** @return list<ItemDraft> */
    public function findByStatus(ItemDraftStatus $status, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM item_draft WHERE status = ? ORDER BY created_at ASC LIMIT ?',
        );
        $stmt->bindValue(1, $status->value);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $drafts = [];
        foreach ($rows as $row) {
            $drafts[] = ItemDraft::fromRow($row);
        }

        return $drafts;
    }

    public function save(ItemDraft $draft): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item_draft SET
                image_path = ?,
                barcode_hint = ?,
                user_data = ?,
                ai_result = ?,
                status = ?,
                auto_register = ?,
                promoted_item_id = ?,
                processing_started_at = ?,
                error_detail = ?,
                created_by = ?,
                updated_at = NOW()
            WHERE id = ?',
        );

        $stmt->execute([
            $draft->imagePath,
            $draft->barcodeHint,
            $draft->userData !== null ? json_encode($draft->userData) : null,
            $draft->aiResult !== null ? json_encode($draft->aiResult) : null,
            $draft->status->value,
            $draft->autoRegister ? 1 : 0,
            $draft->promotedItemId,
            $draft->processingStartedAt?->format('Y-m-d H:i:s'),
            $draft->errorDetail,
            $draft->createdBy,
            $draft->id,
        ]);
    }

    /**
     * @param array<string, mixed>|null $userData
     */
    public function create(
        string $imagePath,
        ?string $barcodeHint,
        ?array $userData,
        ?int $createdBy,
        bool $autoRegister = false,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO item_draft
                (image_path, barcode_hint, user_data, status, auto_register, created_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW(), NOW())',
        );

        $stmt->execute([
            $imagePath,
            $barcodeHint,
            $userData !== null ? json_encode($userData) : null,
            ItemDraftStatus::Queued->value,
            $autoRegister ? 1 : 0,
            $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, ItemDraftStatus $status, ?string $errorDetail = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item_draft SET status = ?, error_detail = ?, updated_at = NOW() WHERE id = ?',
        );

        $stmt->execute([$status->value, $errorDetail, $id]);
    }

    /** @param array<string, mixed> $aiResult */
    public function updateAiResult(int $id, array $aiResult, ItemDraftStatus $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item_draft SET ai_result = ?, status = ?, updated_at = NOW() WHERE id = ?',
        );

        $stmt->execute([json_encode($aiResult), $status->value, $id]);
    }

    public function markProcessing(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item_draft SET status = ?, processing_started_at = NOW(), updated_at = NOW() WHERE id = ?',
        );

        $stmt->execute([ItemDraftStatus::Processing->value, $id]);
    }

    public function markPromoted(int $id, int $itemId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE item_draft SET status = ?, promoted_item_id = ?, updated_at = NOW() WHERE id = ?',
        );

        $stmt->execute([ItemDraftStatus::Confirmed->value, $itemId, $id]);
    }
}
