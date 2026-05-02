<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

interface KeywordLookupStepInterface
{
    /**
     * @param array<string, mixed> $existing
     *
     * @return array<string, mixed>
     */
    public function run(array $existing): array;
}
