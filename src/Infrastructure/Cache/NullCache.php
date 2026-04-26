<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Cache;

use Saso\Domain\Cache\Cache;
use Saso\Domain\Cache\CacheKey;

/**
 * No-op cache (cf. ADR 0012).
 *
 * Selected by the M6-D2 composition root when:
 *   * `cache.driver = null` in `system_setting`, OR
 *   * the Redis connection probe fails at boot, OR
 *   * `SAFE_MODE=true` in `.env`.
 *
 * Behaviour:
 *   * `get()` always returns `null` — the read-through pattern falls
 *     through to the underlying source.
 *   * `set()` is a no-op so warm-paths stay branch-free.
 *   * `forget()` is a no-op for the same reason.
 *   * `has()` always returns `false`.
 *
 * Operators on shared hosting without Redis get this implementation;
 * the application stays available, just without the cache speedup.
 */
final class NullCache implements Cache
{
    public function get(CacheKey $key): mixed
    {
        return null;
    }

    public function set(CacheKey $key, mixed $value, int $ttlSeconds): void
    {
        // intentional no-op
    }

    public function forget(CacheKey $key): void
    {
        // intentional no-op
    }

    public function has(CacheKey $key): bool
    {
        return false;
    }
}
