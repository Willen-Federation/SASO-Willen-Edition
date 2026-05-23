<?php

declare(strict_types=1);

namespace Saso\Application\Messaging\Handler;

use Psr\Log\LoggerInterface;
use Saso\Application\Ai\AiJudgeAutoSync;
use Saso\Application\Enrichment\AutoRegisterPipeline;
use Saso\Application\Enrichment\DraftData;
use Saso\Application\Enrichment\EnrichmentPipeline;
use Saso\Application\ItemDraft\PromoteDraftToItemService;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\ItemDraft\ItemDraft;
use Saso\Domain\ItemDraft\ItemDraftStatus;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;
use Saso\Domain\Messaging\Message\ProcessItemDraft;

final class ProcessItemDraftHandler
{
    public function __construct(
        private readonly ItemDraftRepository $drafts,
        private readonly EnrichmentPipeline $pipeline,
        private readonly AutoRegisterPipeline $autoPipeline,
        private readonly PromoteDraftToItemService $promoter,
        private readonly FeatureFlagRepository $flags,
        private readonly AiJudgeAutoSync $autoSync,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessItemDraft $message): void
    {
        $this->autoSync->sync();

        $draft = $this->drafts->findById($message->draftId);

        if ($draft === null) {
            $this->logger->warning('ProcessItemDraft: draft not found', ['draft_id' => $message->draftId]);
            return;
        }

        if ($draft->autoRegister && $this->isAutoRegisterEnabled()) {
            $this->handleAutoRegister($draft);
            return;
        }

        $this->handleLegacy($draft);
    }

    private function handleLegacy(ItemDraft $draft): void
    {
        try {
            $this->drafts->markProcessing($draft->id);

            $draftData = new DraftData(
                id: $draft->id,
                imagePath: $draft->imagePath,
                barcodeHint: $draft->barcodeHint,
                userData: $draft->userData,
            );

            $aiResult = $this->pipeline->run($draftData);

            $this->drafts->updateAiResult($draft->id, $aiResult, ItemDraftStatus::Ready);
        } catch (\Throwable $e) {
            $this->drafts->updateStatus($draft->id, ItemDraftStatus::Failed, $e->getMessage());
            throw $e;
        }
    }

    private function handleAutoRegister(ItemDraft $draft): void
    {
        if ($draft->promotedItemId !== null) {
            $this->logger->info('ProcessItemDraft: draft already promoted, skipping', [
                'draft_id' => $draft->id,
                'item_id'  => $draft->promotedItemId,
            ]);
            return;
        }

        try {
            $this->drafts->markProcessing($draft->id);

            $draftData = new DraftData(
                id: $draft->id,
                imagePath: $draft->imagePath,
                barcodeHint: $draft->barcodeHint,
                userData: $draft->userData,
            );

            $aiResult = $this->autoPipeline->run($draftData);
            $this->drafts->updateAiResult($draft->id, $aiResult, ItemDraftStatus::Processing);

            $reloaded = $this->drafts->findById($draft->id) ?? $draft;
            $itemId   = $this->promoter->promote($reloaded, $aiResult);

            $this->logger->info('ProcessItemDraft: auto-register promoted draft', [
                'draft_id' => $draft->id,
                'item_id'  => $itemId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('ProcessItemDraft: auto-register failed', [
                'draft_id' => $draft->id,
                'error'    => $e->getMessage(),
            ]);
            // PromoteDraftToItemService records its own Failed status with
            // a more specific error_detail; only fall back here if it didn't.
            $latest = $this->drafts->findById($draft->id);
            if ($latest !== null && $latest->status !== ItemDraftStatus::Failed) {
                $this->drafts->updateStatus($draft->id, ItemDraftStatus::Failed, $e->getMessage());
            }
            throw $e;
        }
    }

    private function isAutoRegisterEnabled(): bool
    {
        $flag = $this->flags->findByKey(new FeatureKey('ai.auto_register'));

        return $flag !== null && $flag->enabled;
    }
}
