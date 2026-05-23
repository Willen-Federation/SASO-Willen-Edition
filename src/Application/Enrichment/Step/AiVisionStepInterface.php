<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

interface AiVisionStepInterface
{
    /**
     * @param array<string, mixed> $existing
     *
     * @return array<string, mixed>
     */
    public function run(string $imagePath, ?string $barcodeHint, array $existing): array;

    /**
     * Re-runs AI extraction for a subset of fields. Used by IterativeAiResolver
     * to fill in only the keys that remain empty after the first AI pass.
     *
     * @param array<string, mixed> $existing
     * @param list<string> $missingFields keys from the extraction schema to re-request
     *
     * @return array<string, mixed>
     */
    public function runForFields(
        string $imagePath,
        ?string $barcodeHint,
        array $existing,
        array $missingFields,
    ): array;
}
