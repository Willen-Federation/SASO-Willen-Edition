<?php

declare(strict_types=1);

namespace Saso\Domain\Search;

use InvalidArgumentException;

/**
 * Aggregate response from a {@see SearchIndex} call.
 *
 * `hits` are ordered by score, highest first. `total` is the total
 * count of matching documents (>= count(hits)) — useful for
 * pagination headers. `tookMs` is the engine-reported elapsed time;
 * adapters that don't measure it leave it zero.
 */
final readonly class SearchResult
{
    /**
     * @param list<SearchHit> $hits
     */
    public function __construct(
        public array $hits,
        public int $total,
        public int $tookMs = 0,
    ) {
        if ($total < 0) {
            throw new InvalidArgumentException('SearchResult.total must be ≥ 0.');
        }
        if ($tookMs < 0) {
            throw new InvalidArgumentException('SearchResult.tookMs must be ≥ 0.');
        }
        if (count($hits) > $total) {
            throw new InvalidArgumentException(sprintf(
                'SearchResult: hits (%d) cannot exceed total (%d).',
                count($hits),
                $total,
            ));
        }
    }

    public static function empty(): self
    {
        return new self(hits: [], total: 0);
    }

    public function isEmpty(): bool
    {
        return $this->hits === [];
    }
}
