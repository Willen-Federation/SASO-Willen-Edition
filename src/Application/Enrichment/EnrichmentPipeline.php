<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment;

use Saso\Application\Enrichment\Step\AiVisionStepInterface;
use Saso\Application\Enrichment\Step\IsbnLookupStepInterface;
use Saso\Application\Enrichment\Step\JanLookupStepInterface;
use Saso\Application\Enrichment\Step\KeywordLookupStepInterface;
use Saso\Application\Enrichment\Step\MergeStep;

final class EnrichmentPipeline
{
    public function __construct(
        private readonly IsbnLookupStepInterface $isbnLookup,
        private readonly JanLookupStepInterface $janLookup,
        private readonly AiVisionStepInterface $aiVision,
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

        $base    = $this->merge->merge([], $isbnData, $userProtected);
        $base    = $this->merge->merge($base, $janData, $userProtected);

        $aiData  = $this->aiVision->run($draft->imagePath, $draft->barcodeHint, $base);
        $result  = $this->merge->merge($base, $aiData, $userProtected);

        $keywordData = $this->keywordLookup->run($result);
        $result      = $this->merge->merge($result, $keywordData, $userProtected);

        if ($draft->userData !== null) {
            foreach ($draft->userData as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
