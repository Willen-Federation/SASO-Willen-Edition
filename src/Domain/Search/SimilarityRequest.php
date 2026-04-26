<?php

declare(strict_types=1);

namespace Saso\Domain\Search;

use InvalidArgumentException;

/**
 * Vector-similarity request against {@see SearchIndex} (cf. ADR 0010).
 *
 * `vector` is the query embedding produced by
 * {@see \Saso\Domain\Ai\AiAssistant::embed()}; the index runs k-NN
 * against `text_embedding` (text-mode) or `image_embedding`
 * (image-mode) depending on `mode`.
 *
 * `k` caps the number of hits returned. `filters` allows narrowing
 * the candidate set before the k-NN search runs (e.g.
 * `category_path = "books/jp"`); some implementations push the
 * filter into the k-NN engine, others apply post-filter.
 */
final readonly class SimilarityRequest
{
    public const MODE_TEXT  = 'text';
    public const MODE_IMAGE = 'image';

    public const DEFAULT_K = 10;
    public const MAX_K     = 100;

    /**
     * @param list<float> $vector embedding
     * @param array<string, scalar|list<scalar>> $filters keyword field equalities
     */
    public function __construct(
        public array $vector,
        public string $mode = self::MODE_TEXT,
        public int $k = self::DEFAULT_K,
        public array $filters = [],
    ) {
        if ($vector === []) {
            throw new InvalidArgumentException('SimilarityRequest.vector must not be empty.');
        }
        if (!in_array($mode, [self::MODE_TEXT, self::MODE_IMAGE], true)) {
            throw new InvalidArgumentException(sprintf(
                'SimilarityRequest.mode must be text|image (got %s).',
                $mode,
            ));
        }
        if ($k < 1 || $k > self::MAX_K) {
            throw new InvalidArgumentException(sprintf(
                'SimilarityRequest.k must be in [1, %d] (got %d).',
                self::MAX_K,
                $k,
            ));
        }
    }

    public function dimensions(): int
    {
        return count($this->vector);
    }
}
