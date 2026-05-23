<?php

declare(strict_types=1);

namespace Saso\Application\Auth;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Tiny fixed-window rate limiter used by the REST auth endpoints.
 *
 * The limiter is keyed by an opaque bucket string (the controllers compose
 * one from IP + username so a single attacker cannot lock another user out,
 * and a single user cannot circumvent the limit by rotating IPs).
 *
 * Backing store: append-only JSON file under the configured directory
 * (defaults to `<sys_get_temp_dir>/saso-auth-rl`). Each bucket is a separate
 * file containing the list of attempt timestamps inside the current window.
 * File-backed because the codebase has no shared cache yet, and the OS file
 * lock (LOCK_EX on the per-bucket file) is enough to keep the read /
 * mutate / write cycle race-free under PHP-FPM concurrency.
 *
 * The limiter exposes two operations: {@see check()} (peek at the count,
 * never increments) and {@see register()} (call after a failed attempt to
 * count it toward the limit). A successful attempt should call {@see reset()}
 * so legitimate users do not accidentally lock themselves out by typing the
 * password wrong a few times in a row.
 */
final class RateLimiter
{
    public function __construct(
        private readonly string $storageDir,
        private readonly int $maxAttempts = 10,
        private readonly int $windowSeconds = 300,
    ) {
        if ($maxAttempts < 1) {
            throw new \InvalidArgumentException('RateLimiter.maxAttempts must be >= 1.');
        }
        if ($windowSeconds < 1) {
            throw new \InvalidArgumentException('RateLimiter.windowSeconds must be >= 1.');
        }

        if (!is_dir($this->storageDir) && !@mkdir($this->storageDir, 0o700, true) && !is_dir($this->storageDir)) {
            throw new \RuntimeException(sprintf(
                'RateLimiter: could not create storage directory %s.',
                $this->storageDir,
            ));
        }
    }

    /**
     * Build a limiter rooted in the system temp dir.
     *
     * Production deployments should pass an explicit path (typically under
     * `var/cache/auth-rl`) so the buckets survive process restarts and stay
     * out of `/tmp` (which the OS may prune aggressively).
     */
    public static function default(?int $maxAttempts = null, ?int $windowSeconds = null): self
    {
        return new self(
            storageDir: sys_get_temp_dir().'/saso-auth-rl',
            maxAttempts: $maxAttempts ?? 10,
            windowSeconds: $windowSeconds ?? 300,
        );
    }

    /**
     * True when the bucket is below the failure threshold and another attempt
     * may proceed. Does NOT increment the counter — call {@see register()}
     * after the attempt finishes (and only on failure).
     */
    public function isAllowed(string $bucket, ?DateTimeImmutable $now = null): bool
    {
        $now      = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $attempts = $this->loadActiveAttempts($bucket, $now);

        return count($attempts) < $this->maxAttempts;
    }

    /**
     * Best-effort estimate of the seconds the caller should wait before the
     * bucket clears, or null when the bucket has free slots.
     */
    public function retryAfterSeconds(string $bucket, ?DateTimeImmutable $now = null): ?int
    {
        $now      = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $attempts = $this->loadActiveAttempts($bucket, $now);

        if (count($attempts) < $this->maxAttempts) {
            return null;
        }

        // Oldest attempt in the window exits the window at oldest + windowSeconds.
        $oldest = $attempts[0];
        $clears = $oldest + $this->windowSeconds;
        $delta  = $clears - $now->getTimestamp();

        return max($delta, 1);
    }

    /**
     * Record one failed attempt against the bucket. Returns the new attempt
     * count inside the active window.
     */
    public function register(string $bucket, ?DateTimeImmutable $now = null): int
    {
        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->mutate($bucket, function (array $attempts) use ($now): array {
            $cutoff   = $now->getTimestamp() - $this->windowSeconds;
            $active   = array_values(array_filter($attempts, static fn (int $t): bool => $t > $cutoff));
            $active[] = $now->getTimestamp();

            return $active;
        });
    }

    /**
     * Forget all attempts for this bucket. Call after a successful auth so
     * that a legitimate user who mistyped a few times in a row is not
     * eventually locked out.
     */
    public function reset(string $bucket): void
    {
        $path = $this->bucketPath($bucket);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @return list<int> sorted ascending
     */
    private function loadActiveAttempts(string $bucket, DateTimeImmutable $now): array
    {
        $path = $this->bucketPath($bucket);
        if (!is_file($path)) {
            return [];
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }

        $decoded = json_decode($contents, associative: true);
        if (!is_array($decoded)) {
            return [];
        }

        $cutoff  = $now->getTimestamp() - $this->windowSeconds;
        $active  = [];
        foreach ($decoded as $ts) {
            if (is_int($ts) && $ts > $cutoff) {
                $active[] = $ts;
            }
        }
        sort($active);

        return $active;
    }

    /**
     * @param callable(list<int>): list<int> $mutator
     */
    private function mutate(string $bucket, callable $mutator): int
    {
        $path = $this->bucketPath($bucket);
        $fp   = @fopen($path, 'c+b');
        if ($fp === false) {
            throw new \RuntimeException(sprintf(
                'RateLimiter: could not open bucket file %s.',
                $path,
            ));
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \RuntimeException('RateLimiter: could not acquire exclusive lock.');
            }

            $raw      = stream_get_contents($fp);
            $existing = [];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, associative: true);
                if (is_array($decoded)) {
                    foreach ($decoded as $ts) {
                        if (is_int($ts)) {
                            $existing[] = $ts;
                        }
                    }
                }
            }

            $next = $mutator($existing);

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode($next));
            fflush($fp);

            return count($next);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private function bucketPath(string $bucket): string
    {
        $safe = hash('sha256', $bucket);

        return $this->storageDir.'/'.$safe.'.json';
    }
}
