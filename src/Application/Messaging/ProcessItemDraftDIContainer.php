<?php

declare(strict_types=1);

namespace Saso\Application\Messaging;

use Saso\Application\Ai\AiJudgeAutoSync;
use Saso\Application\Enrichment\EnrichmentPipeline;
use Saso\Application\Enrichment\Step\AiVisionStep;
use Saso\Application\Enrichment\Step\IsbnLookupStep;
use Saso\Application\Enrichment\Step\JanLookupStep;
use Saso\Application\Enrichment\Step\KeywordLookupStep;
use Saso\Application\Enrichment\Step\MergeStep;
use Saso\Application\Messaging\Handler\ProcessItemDraftHandler;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Infrastructure\Ai\AiAssistantFactory;
use Saso\Infrastructure\Logging\MonologFactory;

final class ProcessItemDraftDIContainer
{
    public static function createHandler(
        ItemDraftRepository $draftRepository,
        SystemSettingService $settingService,
        FeatureFlagRepository $flagRepository,
    ): ProcessItemDraftHandler {
        $logger = MonologFactory::create();

        $isbnLookup = new IsbnLookupStep();
        $janLookup = new JanLookupStep();
        $aiAssistant = AiAssistantFactory::forVision($settingService);
        $aiVision = new AiVisionStep($aiAssistant, $flagRepository);
        $keywordLookup = new KeywordLookupStep();
        $merge = new MergeStep();

        $pipeline = new EnrichmentPipeline(
            $isbnLookup,
            $janLookup,
            $aiVision,
            $keywordLookup,
            $merge,
        );

        $autoSync = new AiJudgeAutoSync($settingService, $flagRepository);

        return new ProcessItemDraftHandler(
            $draftRepository,
            $pipeline,
            $autoSync,
            $logger,
        );
    }
}
