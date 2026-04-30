<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment;

final readonly class DraftData
{
    public function __construct(
        public int $id,
        public string $imagePath,
        public ?string $barcodeHint,
        /** @var array<string, mixed>|null */
        public ?array $userData,
    ) {
    }
}
