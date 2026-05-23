<?php

declare(strict_types=1);

namespace Saso\Application\ItemDraft;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Saso\Application\Category\CategoryHintResolver;
use Saso\Domain\ItemDraft\ItemDraft;
use Saso\Domain\ItemDraft\ItemDraftStatus;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;

/**
 * Promotes an enriched item_draft row directly into an `item` row.
 *
 * Invariants:
 *  - Idempotent: a re-run for the same draft returns the existing item id
 *    without issuing a second INSERT (worker retry safety).
 *  - Atomic: the INSERT and the draft-status update happen in a single
 *    transaction; on failure the draft is marked Failed with error_detail.
 *  - Refuses to promote when the AI/lookup pipeline did not produce a name
 *    or when no category exists at all.
 */
final class PromoteDraftToItemService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ItemDraftRepository $drafts,
        private readonly CategoryHintResolver $categoryResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $aiResult merged enrichment output
     */
    public function promote(ItemDraft $draft, array $aiResult): int
    {
        $existingId = $this->existingPromotedId($draft);
        if ($existingId !== null) {
            return $existingId;
        }

        $name = $this->normaliseString($aiResult['item_name'] ?? null);
        if ($name === null) {
            $this->drafts->updateStatus(
                $draft->id,
                ItemDraftStatus::Failed,
                'auto-register: item_name could not be resolved by lookups or AI.',
            );
            throw new RuntimeException('PromoteDraftToItemService: item_name is empty for draft '.$draft->id);
        }

        $categoryId = $this->categoryResolver->resolve($this->normaliseString($aiResult['category_hint'] ?? null));
        if ($categoryId === null) {
            $this->drafts->updateStatus(
                $draft->id,
                ItemDraftStatus::Failed,
                'auto-register: no category available — seed at least one category row.',
            );
            throw new RuntimeException('PromoteDraftToItemService: category table empty for draft '.$draft->id);
        }

        $jan       = $this->normaliseString($aiResult['jan_code'] ?? null);
        $isbn      = $this->normaliseString($aiResult['isbn'] ?? null);
        $labelCode = $this->normaliseString($aiResult['label_code'] ?? null);
        $note      = $this->buildNote($aiResult);
        $price     = $this->normalisePrice($aiResult['price'] ?? null);
        $stock     = 0;
        $now       = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();
        try {
            $itemId = $this->insertItem($name, $categoryId, $jan, $isbn, $labelCode, $note, $price, $stock, $now);
            $this->drafts->markPromoted($draft->id, $itemId);
            $this->pdo->commit();

            return $itemId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->drafts->updateStatus(
                $draft->id,
                ItemDraftStatus::Failed,
                'auto-register: insert failed — '.$e->getMessage(),
            );
            throw $e;
        }
    }

    private function existingPromotedId(ItemDraft $draft): ?int
    {
        if ($draft->promotedItemId !== null) {
            return $draft->promotedItemId;
        }

        $stmt = $this->pdo->prepare('SELECT promoted_item_id FROM item_draft WHERE id = :id');
        $stmt->execute(['id' => $draft->id]);
        $value = $stmt->fetchColumn();

        if ($value === false || $value === null) {
            return null;
        }

        return (int) $value;
    }

    private function insertItem(
        string $name,
        int $categoryId,
        ?string $janCode,
        ?string $isbnCode,
        ?string $labelCode,
        ?string $note,
        int $price,
        int $stock,
        string $now,
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO item (name, category_id, jan_code, isbn, label_code, note, price, stock, status, created_at, updated_at) '
            .'VALUES (:name, :category_id, :jan_code, :isbn, :label_code, :note, :price, :stock, :status, :created_at, :updated_at)',
        );
        $stmt->bindValue('name', $name);
        $stmt->bindValue('category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue('jan_code', $janCode);
        $stmt->bindValue('isbn', $isbnCode);
        $stmt->bindValue('label_code', $labelCode);
        $stmt->bindValue('note', $note);
        $stmt->bindValue('price', $price, PDO::PARAM_INT);
        $stmt->bindValue('stock', $stock, PDO::PARAM_INT);
        $stmt->bindValue('status', 'active');
        $stmt->bindValue('created_at', $now);
        $stmt->bindValue('updated_at', $now);
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    private function normaliseString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param array<string, mixed> $aiResult
     */
    private function buildNote(array $aiResult): ?string
    {
        $description  = $this->normaliseString($aiResult['description'] ?? null);
        $manufacturer = $this->normaliseString($aiResult['manufacturer'] ?? null);

        $parts = [];
        if ($description !== null) {
            $parts[] = $description;
        }
        if ($manufacturer !== null) {
            $parts[] = 'メーカー: '.$manufacturer;
        }
        if ($parts === []) {
            return null;
        }

        return mb_substr(implode("\n", $parts), 0, 255);
    }

    private function normalisePrice(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, $value);
        }
        if (is_string($value) && is_numeric($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }
}
