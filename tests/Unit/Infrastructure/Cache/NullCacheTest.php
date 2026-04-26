<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Cache;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Cache\Cache;
use Saso\Domain\Cache\CacheKey;
use Saso\Infrastructure\Cache\NullCache;

final class NullCacheTest extends TestCase
{
    public function testImplementsCache(): void
    {
        self::assertInstanceOf(Cache::class, new NullCache());
    }

    public function testGetAlwaysReturnsNull(): void
    {
        $cache = new NullCache();
        $key   = new CacheKey('any:key');

        self::assertNull($cache->get($key));
        // Even after a "set" the value never appears.
        $cache->set($key, ['value' => 1], 60);
        self::assertNull($cache->get($key));
    }

    public function testHasAlwaysReturnsFalse(): void
    {
        $cache = new NullCache();
        $key   = new CacheKey('any:key');

        self::assertFalse($cache->has($key));
        $cache->set($key, 'value', 60);
        self::assertFalse($cache->has($key));
    }

    public function testForgetIsNoOp(): void
    {
        $cache = new NullCache();
        // Should not throw.
        $cache->forget(new CacheKey('any:key'));
        self::assertTrue(true); // assertion required for non-empty test
    }

    public function testSetIsNoOpAcrossMixedTypes(): void
    {
        $cache = new NullCache();
        $cache->set(new CacheKey('a'), 'string', 60);
        $cache->set(new CacheKey('b'), 42, 60);
        $cache->set(new CacheKey('c'), ['nested' => true], 60);
        $cache->set(new CacheKey('d'), null, 60);

        self::assertNull($cache->get(new CacheKey('a')));
        self::assertNull($cache->get(new CacheKey('b')));
        self::assertNull($cache->get(new CacheKey('c')));
        self::assertNull($cache->get(new CacheKey('d')));
    }
}
