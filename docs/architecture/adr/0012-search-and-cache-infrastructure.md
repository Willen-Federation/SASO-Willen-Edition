# 0012 — Search + cache infrastructure: OpenSearch primary, Redis cache

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

[ADR 0010](0010-vector-search-via-opensearch.md) commits to OpenSearch as the search and vector tier. M6 also requires that operations on hot paths (item detail rendering, admin dashboard counts, login-screen provider list) stay snappy on busy instances. Hitting MariaDB or OpenSearch for every request stops scaling around the time a third operator is concurrently scanning barcodes.

We need:

1. a **shared cache** that survives between requests;
2. a **rate-limit / throttle** for AI calls that bills per token;
3. a **session store** option for operators who run more than one app container.

## Decision Drivers

- **Optional** — operators on shared hosting must still get a working SASO. The cache cannot be load-bearing.
- **Already-justified by ADR 0013** — the background queue needs a reliable transport; Redis gives us both.
- **Self-hostable, mature, single-image** — Redis fits.

## Considered Options

### Option A — APCu (in-process)

* (+) Zero dependency.
* (−) Process-local; multi-container deploys cannot share. Lost on FPM restart.

### Option B — Memcached

* (+) Simpler than Redis.
* (−) No persistence, no streams, no pub/sub. We want the queue + rate-limit primitives Redis provides.

### Option C — Redis 7 (standalone, no cluster)

A single Redis container handles four concerns:

| Use | Key space | TTL | Notes |
|---|---|---|---|
| Cache (hot reads) | `cache:*` | 300-3600 s | Read-through; misses fall through to MariaDB / OpenSearch. |
| Rate limiter (AI calls) | `rl:ai:<provider>:<minute>` | 60 s | INCR + EXPIRE pattern. |
| Session store | `sess:<id>` | session lifetime | Optional; enabled by `SESSION_DRIVER=redis` in `.env`. |
| Queue transport | reserved by Symfony Messenger (ADR 0013) | per-message | Streams (`XADD`/`XREADGROUP`). |

## Decision Outcome

**Chosen option: C — Redis 7, single container, four concerns.**

### Cache layer

```php
namespace Saso\Domain\Cache;

interface Cache {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttlSeconds): void;
    public function forget(string $key): void;
    public function tags(string ...$tags): TaggedCache;
}
```

Two implementations:

- `Saso\Infrastructure\Cache\RedisCache` — production. Uses Redis hashes for tagged invalidation.
- `Saso\Infrastructure\Cache\NullCache` — fallback when Redis is unavailable. Read-through always misses; writes are no-ops.

Bootstrap reads `cache.driver` from `system_setting` (`redis` | `null`) and constructs the right adapter. `SAFE_MODE=true` forces `NullCache`.

### Rate limiting

`Saso\Infrastructure\Ai\RedisAiRateLimiter` wraps `AiAssistant` (cf. ADR 0009). Per-provider per-minute caps configurable via `system_setting` (`ai.rate_limit.openai = 60`). Excess calls throw `SASO-AI-8002` (rate-limited).

### Session store

Honours `SESSION_DRIVER` in `.env`:

- `file` (default; legacy behaviour) — PHP's built-in file handler.
- `redis` — uses Redis as the session backing store via `phpredis` extension or `predis/predis` Composer fallback.

The session-cookie hardening from M1 (`HttpOnly` / `SameSite=Lax` / `Secure`) is unchanged.

### Failover

Every call to Redis is wrapped in a 200 ms timeout. On a connection error the adapter logs at `warning` level (Monolog) and falls through to the null path. The application stays available; operators see a warning banner if cache is down for more than 5 minutes.

## Consequences

- Redis joins `docker-compose.yml` behind a `--profile cache` flag (matches the existing `--profile sso` for Keycloak).
- Operators on shared hosting without Redis get the `NullCache` path. The product still works.
- Symfony Messenger (ADR 0013) transports default to Redis Streams when `cache.driver = redis`; otherwise they fall back to a Doctrine transport against MariaDB.
- We do not adopt Redis Cluster. Operators with that scale of traffic are out of M6 scope.
