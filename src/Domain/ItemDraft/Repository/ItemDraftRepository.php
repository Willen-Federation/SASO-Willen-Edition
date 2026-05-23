<?php

declare(strict_types=1);

namespace Saso\Domain\ItemDraft\Repository;

use Saso\Domain\ItemDraft\ItemDraft;
use Saso\Domain\ItemDraft\ItemDraftStatus;

interface ItemDraftRepository
{
    public function findById(int $id): ?ItemDraft;

    /** @return list<ItemDraft> */
    public function findByStatus(ItemDraftStatus $status, int $limit = 50): array;

    public function save(ItemDraft $draft): void;

    /**
     * @param array<string, mixed>|null $userData
     */
    public function create(
        string $imagePath,
        ?string $barcodeHint,
        ?array $userData,
        ?int $createdBy,
        bool $autoRegister = false,
    ): int;

    public function updateStatus(int $id, ItemDraftStatus $status, ?string $errorDetail = null): void;

    /**
     * @param array<string, mixed> $aiResult
     */
    public function updateAiResult(int $id, array $aiResult, ItemDraftStatus $status): void;

    public function markProcessing(int $id): void;

    /**
     * Atomically attaches the promoted item id to the draft and sets status=confirmed.
     * Used by PromoteDraftToItemService as the final step of a successful promotion.
     */
    public function markPromoted(int $id, int $itemId): void;
}
