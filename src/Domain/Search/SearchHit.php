<?php

declare(strict_types=1);

namespace Saso\Domain\Search;

use InvalidArgumentException;

/**
 * One result from a {@see SearchIndex} call.
 *
 * `score` is provider-relative — BM25 scores from a keyword search
 * cannot be compared with cosine similarity from a k-NN search.
 * Callers compare hits within the same `SearchResult`, never across
 * heterogeneous result sets.
 *
 * `document` carries the indexed shape minus internal fields;
 * controllers project it into the wire format. `id` is the SASO
 * domain id (matches `Item.id` for `saso_items` hits).
 */
final readonly class SearchHit
{
    /**
     * @param array<string, mixed> $document
     */
    public function __construct(
        public int $id,
        public float $score,
        public array $document,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('SearchHit.id must be a positive integer.');
        }
        if ($score < 0.0) {
            throw new InvalidArgumentException('SearchHit.score must be ≥ 0.');
        }
    }
}
