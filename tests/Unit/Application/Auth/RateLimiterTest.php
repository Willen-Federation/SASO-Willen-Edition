<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Auth;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Saso\Application\Auth\RateLimiter;

final class RateLimiterTest extends TestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/saso-rate-limiter-test-'.bin2hex(random_bytes(4));
        mkdir($this->tempDir, 0o700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testNewBucketIsAllowed(): void
    {
        $limiter = new RateLimiter($this->tempDir, maxAttempts: 3, windowSeconds: 60);
        self::assertTrue($limiter->isAllowed('fresh'));
        self::assertNull($limiter->retryAfterSeconds('fresh'));
    }

    public function testRegisterIncrementsCountAndBlocksOnceMaxReached(): void
    {
        $limiter = new RateLimiter($this->tempDir, maxAttempts: 3, windowSeconds: 60);

        self::assertSame(1, $limiter->register('alice'));
        self::assertSame(2, $limiter->register('alice'));
        self::assertSame(3, $limiter->register('alice'));

        self::assertFalse($limiter->isAllowed('alice'));
        self::assertNotNull($limiter->retryAfterSeconds('alice'));
    }

    public function testWindowExpiryReleasesBucket(): void
    {
        $limiter = new RateLimiter($this->tempDir, maxAttempts: 2, windowSeconds: 60);

        $t0 = new DateTimeImmutable('2026-01-01T00:00:00', new DateTimeZone('UTC'));
        $limiter->register('alice', $t0);
        $limiter->register('alice', $t0);
        self::assertFalse($limiter->isAllowed('alice', $t0));

        $tLater = $t0->modify('+120 seconds');
        self::assertTrue($limiter->isAllowed('alice', $tLater));
    }

    public function testResetClearsCounter(): void
    {
        $limiter = new RateLimiter($this->tempDir, maxAttempts: 2, windowSeconds: 60);

        $limiter->register('alice');
        $limiter->register('alice');
        self::assertFalse($limiter->isAllowed('alice'));

        $limiter->reset('alice');
        self::assertTrue($limiter->isAllowed('alice'));
    }

    public function testBucketsAreIsolated(): void
    {
        $limiter = new RateLimiter($this->tempDir, maxAttempts: 2, windowSeconds: 60);

        $limiter->register('alice');
        $limiter->register('alice');

        self::assertFalse($limiter->isAllowed('alice'));
        self::assertTrue($limiter->isAllowed('bob'));
    }

    public function testRetryAfterIsPositiveAfterBlock(): void
    {
        $limiter = new RateLimiter($this->tempDir, maxAttempts: 1, windowSeconds: 30);
        $now     = new DateTimeImmutable('2026-01-01T00:00:00', new DateTimeZone('UTC'));

        $limiter->register('alice', $now);

        $delta = $limiter->retryAfterSeconds('alice', $now);
        self::assertNotNull($delta);
        self::assertGreaterThan(0, $delta);
        self::assertLessThanOrEqual(30, $delta);
    }

    public function testZeroMaxAttemptsIsRejectedAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RateLimiter($this->tempDir, maxAttempts: 0, windowSeconds: 60);
    }

    public function testZeroWindowIsRejectedAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RateLimiter($this->tempDir, maxAttempts: 1, windowSeconds: 0);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
