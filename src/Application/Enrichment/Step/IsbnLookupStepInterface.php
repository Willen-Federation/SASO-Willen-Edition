<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

interface IsbnLookupStepInterface
{
    /**
     * @return array<string, mixed>
     */
    public function run(?string $barcodeHint): array;
}
