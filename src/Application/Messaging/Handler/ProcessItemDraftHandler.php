<?php

declare(strict_types=1);

namespace Saso\Application\Messaging\Handler;

use Psr\Log\LoggerInterface;
use Saso\Application\Enrichment\DraftData;
use Saso\Application\Enrichment\EnrichmentPipeline;
use Saso\Domain\ItemDraft\ItemDraftStatus;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;
use Saso\Domain\Messaging\Message\ProcessItemDraft;

final class ProcessItemDraftHandler
{
    public function __construct(
        private readonly ItemDraftRepository $drafts,
        private readonly EnrichmentPipeline $pipeline,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessItemDraft $message): void
    {
        $draft = $this->drafts->findById($message->draftId);

        if ($draft === null) {
            $this->logger->warning('ProcessItemDraft: draft not found', ['draft_id' => $message->draftId]);
            return;
        }

        try {
            $this->drafts->markProcessing($message->draftId);

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
}
