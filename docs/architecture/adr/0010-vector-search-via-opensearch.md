# 0010 — Vector embeddings + image search via OpenSearch k-NN

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

The M6 scope requires:

- **Image-based item search** — operators upload a photo of a product, the system returns the closest matches (an image-embedding nearest-neighbour query).
- **Similar-product suggestions** at registration time — when an operator enters a new item, surface candidates likely to be duplicates or variants.
- **Keyword search** — full-text queries across item titles, descriptions, attribute values, and storage location.

We need an indexing tier that handles both vector similarity and full-text scoring, ships as a single component, and runs reasonably on shared / single-host deployments.

## Decision Drivers

- **One index, both queries** — running a vector DB next to a search engine doubles operations.
- **Self-hostable** — operators must be able to run it on their own boxes; no SaaS dependencies on the data path.
- **MariaDB compatibility** — our primary store is MariaDB (cf. ADR 0007) and it has no native vector type. Embeddings must live elsewhere.
- **Composes with [ADR 0009](0009-multi-llm-gateway.md)** — embedding production is independent of storage.

## Considered Options

### Option A — pgvector (would require migrating to PostgreSQL)

* (+) Mature, single-store solution.
* (−) Requires migrating off MariaDB. Out of scope and would invalidate every existing migration.

### Option B — Dedicated vector DB (Qdrant, Milvus, Weaviate)

* (+) Best-in-class vector performance.
* (−) Adds a second deployable component. Full-text search still needs another tool (or we accept poor LIKE-based queries).
* (−) Two operational dependencies for the operator.

### Option C — OpenSearch with k-NN plugin

OpenSearch ships native k-NN (HNSW) and Lucene full-text in one engine. Indexing an item produces both a `text` field (for BM25 scoring on title/description/attributes) and a `knn_vector` field (for image / text embedding similarity). One query API, one operational footprint.

* (+) Single component for both query types.
* (+) Apache 2.0, fully self-hostable, supports JVM-tunable resource limits.
* (+) Mature PHP client (`opensearch-project/opensearch-php`).
* (+) Composes with the OpenFeature flag for gradual rollout — when the index is unavailable the application falls back to a `NullSearchIndex` (MariaDB LIKE).
* (−) JVM dependency means another container in `docker-compose.yml`. Acceptable cost; we already run MariaDB and (optionally) Keycloak.
* (−) Index rebuilds are slow for large catalogues. M6 ships an offline reindex script (`scripts/reindex_opensearch.php`).

## Decision Outcome

**Chosen option: C — OpenSearch with k-NN.**

### Index design

Two primary indices:

1. `saso_items` — one document per `Item`. Fields:
   - `id` (long, pk)
   - `title` (text + keyword sub-field)
   - `description` (text)
   - `barcode` (keyword) — ISBN / EAN / JAN / UPC
   - `category_path` (keyword array)
   - `storage_location_code` (keyword)
   - `attributes` (nested: `name` keyword + `value` text)
   - `text_embedding` (knn_vector, dim=`embedding.dim` from `system_setting`)
   - `image_embedding` (knn_vector, dim same as above; multi-valued for items with multiple photos)
2. `saso_storage_locations` — one document per location code (warehouse → row → column → bin). Used by location autocomplete.

Embedding dimensionality is configurable so operators can switch models (e.g. 1536 for `text-embedding-3-large`, 768 for `gemini-embedding-001`) without altering the index by hand. The reindex script reads `ai.embedding.dim` and rebuilds.

### Code shape

```
src/
├── Domain/
│   └── Search/
│       ├── SearchIndex.php          # interface
│       ├── SearchQuery.php          # value object
│       ├── SearchResult.php         # value object
│       └── SimilarityRequest.php    # vector + k
└── Infrastructure/
    └── Search/
        ├── OpenSearchSearchIndex.php
        ├── NullSearchIndex.php      # fallback when OpenSearch is down
        └── OpenSearchClientFactory.php
```

### Indexing pipeline

Item writes (insert, update, delete) emit a domain event; a Symfony Messenger handler (cf. ADR 0013) calls `SearchIndex::upsert()` / `delete()`. The embedding payload is produced via `AiAssistant::embed()` (ADR 0009) — async so the user-facing transaction commits without waiting on the embedding call.

### Cache layer

A Redis cache (cf. ADR 0012) sits in front of the most common queries (top-N popular keyword searches, recent similarity searches). Cache misses fall through to OpenSearch.

### Failure modes

- OpenSearch unreachable → controller falls back to `NullSearchIndex`, which serves results from MariaDB LIKE (degraded but available).
- Embedding provider unreachable → similarity search returns empty with a warning banner; full-text still works.
- Index drift (item updated but indexer not yet processed) → operators can trigger a row-level reindex from the admin UI.

## Consequences

* OpenSearch becomes a runtime dependency of M6 features. The `docker-compose.yml` gains an `opensearch` service behind a `--profile search` flag.
* Embeddings live in OpenSearch only — MariaDB never stores vectors. Rotating the embedding model is a reindex, not a schema migration.
* Two query types (keyword + similarity) hit one engine, one client, one set of operational concerns.
* Operators who don't want OpenSearch get the `NullSearchIndex` path. The product still works; search ranking just falls back to LIKE.
* Future work — multi-tenant isolation (per-organisation indices) is not addressed here; M6 assumes single-tenant deployments.
