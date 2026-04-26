<?php

declare(strict_types=1);

namespace Saso\Domain\Search;

use InvalidArgumentException;

/**
 * Keyword-search request against {@see SearchIndex} (cf. ADR 0010).
 *
 * The `text` field drives BM25 scoring against title + description +
 * attribute values. `filters` adds equality constraints
 * (`category_path = "books/jp"`, `storage_location_code = "WH1-A-03"`).
 * `limit` + `offset` paginate; `sort` is an optional override of the
 * default relevance ordering.
 */
final readonly class SearchQuery
{
    public const DEFAULT_LIMIT = 20;
    public const MAX_LIMIT     = 100;

    public const SORT_RELEVANCE = 'relevance';
    public const SORT_NEWEST    = 'newest';
    public const SORT_OLDEST    = 'oldest';

    /**
     * @param array<string, scalar|list<scalar>> $filters keyword field equalities
     */
    public function __construct(
        public string $text,
        public array $filters = [],
        public int $limit = self::DEFAULT_LIMIT,
        public int $offset = 0,
        public string $sort = self::SORT_RELEVANCE,
    ) {
        if ($text === '') {
            throw new InvalidArgumentException('SearchQuery.text must not be empty.');
        }
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw new InvalidArgumentException(sprintf(
                'SearchQuery.limit must be in [1, %d] (got %d).',
                self::MAX_LIMIT,
                $limit,
            ));
        }
        if ($offset < 0) {
            throw new InvalidArgumentException('SearchQuery.offset must be ≥ 0.');
        }
        if (!in_array(
            $sort,
            [self::SORT_RELEVANCE, self::SORT_NEWEST, self::SORT_OLDEST],
            true,
        )) {
            throw new InvalidArgumentException(sprintf(
                'SearchQuery.sort must be one of relevance|newest|oldest (got %s).',
                $sort,
            ));
        }
    }
}
