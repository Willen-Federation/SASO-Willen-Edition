<?php

declare(strict_types=1);

namespace Saso\Domain\Search;

/**
 * Search + similarity contract (cf. ADR 0010).
 *
 * Implementations:
 *   * `OpenSearchSearchIndex` (M6-D3) — production. k-NN + BM25 in
 *     one engine.
 *   * `NullSearchIndex` (this PR) — fallback. `search()` always
 *     returns the empty result; `findSimilar()` likewise. Writes are
 *     no-ops. Selected when:
 *       * `search.driver = null` in `system_setting`, OR
 *       * the OpenSearch connection probe fails at boot, OR
 *       * `SAFE_MODE=true` in `.env`.
 *
 * The two query methods are intentionally distinct — keyword search
 * (`search()`) and vector similarity (`findSimilar()`) have different
 * semantics, different scoring, and different request shapes. Hybrid
 * queries that combine both go through `search()` with an additional
 * vector field on `SearchQuery` in a future revision.
 *
 * Writes (`upsert()`, `delete()`) are dispatched from
 * `Application/Messaging/Handler/IndexItemHandler` after the M6-D3
 * wiring; the message-driven path keeps the request transaction off
 * the search engine's hot path.
 */
interface SearchIndex
{
    public function search(SearchQuery $query): SearchResult;

    public function findSimilar(SimilarityRequest $request): SearchResult;

    /**
     * Inserts or replaces the document for the given id.
     *
     * @param array<string, mixed> $document
     */
    public function upsert(int $id, array $document): void;

    public function delete(int $id): void;
}
