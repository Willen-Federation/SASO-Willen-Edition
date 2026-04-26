<?php

declare(strict_types=1);

namespace Saso\Domain\Cache;

/**
 * Read-through cache contract (cf. ADR 0012).
 *
 * Two implementations:
 *   * `Saso\Infrastructure\Cache\RedisCache` — production. Backed by
 *     Predis; honours TTL via Redis `EX`. (Lands in M6-D2.)
 *   * `Saso\Infrastructure\Cache\NullCache` — fallback when Redis is
 *     unavailable, or always-on when `SAFE_MODE=true`. Reads always
 *     miss; writes are no-ops; `forget()` is a no-op.
 *
 * Values are arbitrary JSON-encodable payloads. Adapters serialise on
 * the way in and parse on the way out — call sites pass and receive
 * native PHP arrays / scalars / `null`. Resources, closures, and
 * non-`JsonSerializable` objects are rejected (the adapter raises
 * `InvalidArgumentException` from `set()`).
 *
 * Concurrency: read-then-write races on a busy key are tolerated.
 * Two writers may overwrite each other; the cache is best-effort. Hot
 * paths that need stronger guarantees should use the database
 * transaction layer instead.
 */
interface Cache
{
    public function get(CacheKey $key): mixed;

    public function set(CacheKey $key, mixed $value, int $ttlSeconds): void;

    public function forget(CacheKey $key): void;

    /**
     * Returns whether the entry exists and has not expired. Cheaper
     * than {@see get()} for boolean-only callers because adapters can
     * issue an `EXISTS` rather than a full `GET` + parse.
     */
    public function has(CacheKey $key): bool;
}
