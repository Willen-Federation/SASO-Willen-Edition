<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Search;

use Saso\Domain\Search\SearchIndex;
use Saso\Domain\Search\SearchQuery;
use Saso\Domain\Search\SearchResult;
use Saso\Domain\Search\SimilarityRequest;

/**
 * No-op {@see SearchIndex} (cf. ADR 0010).
 *
 * Selected by the M6-D3 composition root when:
 *   * `search.driver = null` in `system_setting`, OR
 *   * the OpenSearch connection probe fails at boot, OR
 *   * `SAFE_MODE=true` in `.env`.
 *
 * Behaviour:
 *   * `search()` always returns the empty result.
 *   * `findSimilar()` always returns the empty result.
 *   * `upsert()` / `delete()` are no-ops so write paths stay
 *     branch-free.
 *
 * Operators on shared hosting without OpenSearch get this
 * implementation; the application stays available, search just
 * returns no hits (the legacy MariaDB LIKE fallback in the admin UI
 * carries on serving keyword queries).
 */
final class NullSearchIndex implements SearchIndex
{
    public function search(SearchQuery $query): SearchResult
    {
        return SearchResult::empty();
    }

    public function findSimilar(SimilarityRequest $request): SearchResult
    {
        return SearchResult::empty();
    }

    public function upsert(int $id, array $document): void
    {
        // intentional no-op
    }

    public function delete(int $id): void
    {
        // intentional no-op
    }
}
