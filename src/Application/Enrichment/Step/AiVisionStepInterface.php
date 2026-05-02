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
}
