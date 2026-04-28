<?php

declare(strict_types=1);

namespace Saso\Domain\LabelSheetLayout;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate root for one row of `label_sheet_layout`.
 *
 * Carries enough geometry to render an A4 (or US Letter) sheet of labels:
 * the grid (`columns × rows`), per-label dimensions, page-margin offsets,
 * and inter-label gaps. The legacy `entity\Label` structure is a strict
 * subset of this — operators who used a custom dimension can keep doing so
 * while seeded rows ship vendor-confirmed numbers.
 */
final readonly class LabelSheetLayout
{
    public const PAPER_A4     = 'A4';
    public const PAPER_LETTER = 'Letter';

    public function __construct(
        public int $id,
        public string $code,
        public string $vendor,
        public string $productNameEn,
        public string $productNameJa,
        public string $paperSize,
        public int $columns,
        public int $rows,
        public float $labelWidthMm,
        public float $labelHeightMm,
        public float $marginTopMm,
        public float $marginLeftMm,
        public float $gapXMm,
        public float $gapYMm,
        public ?float $cornerRadiusMm,
        public bool $isActive,
        public bool $isSeeded,
        public bool $isVerified,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        if ($id < 1)        throw new InvalidArgumentException('id must be >= 1.');
        if ($columns < 1)   throw new InvalidArgumentException('columns must be >= 1.');
        if ($rows < 1)      throw new InvalidArgumentException('rows must be >= 1.');
        if ($labelWidthMm  <= 0) throw new InvalidArgumentException('labelWidthMm must be > 0.');
        if ($labelHeightMm <= 0) throw new InvalidArgumentException('labelHeightMm must be > 0.');
    }

    /**
     * Total slots per sheet — used by the mint-flow to right-size batches.
     */
    public function slotsPerSheet(): int
    {
        return $this->columns * $this->rows;
    }
}
