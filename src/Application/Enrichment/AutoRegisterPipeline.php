<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment;

use Saso\Application\Enrichment\Step\IsbnLookupStepInterface;
use Saso\Application\Enrichment\Step\JanLookupStepInterface;
use Saso\Application\Enrichment\Step\KeywordLookupStepInterface;
use Saso\Application\Enrichment\Step\MergeStep;

/**
 * Variation of {@see EnrichmentPipeline} that swaps the single AI vision
 * call for an iterative re-prompt loop. Used only by the auto-register
 * flow; the legacy draft confirmation flow keeps the existing
 * {@see EnrichmentPipeline} unchanged.
 */
final class AutoRegisterPipeline
{
    public function __construct(
        private readonly IsbnLookupStepInterface $isbnLookup,
        private readonly JanLookupStepInterface $janLookup,
        private readonly IterativeAiResolver $aiResolver,
        private readonly KeywordLookupStepInterface $keywordLookup,
        private readonly MergeStep $merge,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(DraftData $draft): array
    {
        $userProtected = $draft->userData !== null ? array_keys($draft->userData) : [];

        $isbnData = $this->isbnLookup->run($draft->barcodeHint);
        $janData  = $this->janLookup->run($draft->barcodeHint);

        $base = $this->merge->merge([], $isbnData, $userProtected);
        $base = $this->merge->merge($base, $janData, $userProtected);

        $base = $this->aiResolver->run($base, $draft->imagePath, $draft->barcodeHint, $userProtected);

        $keywordData = $this->keywordLookup->run($base);
        $result      = $this->merge->merge($base, $keywordData, $userProtected);

        if ($draft->userData !== null) {
            foreach ($draft->userData as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
