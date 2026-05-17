<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Saso\Presentation\Mcp\Bootstrap;

/**
 * Regression coverage for GitHub issue #205 — the MCP Bootstrap mirrors the
 * API Bootstrap's `jwtSecret()` resolver and must fail closed when neither
 * `JWT_SECRET` nor `APP_KEY` is configured to ≥32 bytes.
 */
final class BootstrapCryptoConfigTest extends TestCase
{
    private ?string $savedAppKey;
    private ?string $savedJwtSecret;

    protected function setUp(): void
    {
        $appKey          = getenv('APP_KEY');
        $jwtSecret       = getenv('JWT_SECRET');
        $this->savedAppKey    = $appKey === false ? null : $appKey;
        $this->savedJwtSecret = $jwtSecret === false ? null : $jwtSecret;

        putenv('APP_KEY');
        putenv('JWT_SECRET');
    }

    protected function tearDown(): void
    {
        if ($this->savedAppKey === null) {
            putenv('APP_KEY');
        } else {
            putenv('APP_KEY='.$this->savedAppKey);
        }

        if ($this->savedJwtSecret === null) {
            putenv('JWT_SECRET');
        } else {
            putenv('JWT_SECRET='.$this->savedJwtSecret);
        }
    }

    private function invokeJwtSecret(): string
    {
        $reflection = new ReflectionMethod(Bootstrap::class, 'jwtSecret');
        $reflection->setAccessible(true);

        return (string) $reflection->invoke(null);
    }

    public function testJwtSecretThrowsWhenNeitherSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET');

        $this->invokeJwtSecret();
    }

    public function testJwtSecretThrowsWhenBothTooShort(): void
    {
        putenv('JWT_SECRET=tooShort');
        putenv('APP_KEY=alsoShort');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('insecure fallback');

        $this->invokeJwtSecret();
    }

    public function testJwtSecretPrefersJwtSecretOverAppKey(): void
    {
        $jwtSecret = str_repeat('A', 32);
        $appKey    = str_repeat('B', 32);
        putenv('JWT_SECRET='.$jwtSecret);
        putenv('APP_KEY='.$appKey);

        self::assertSame($jwtSecret, $this->invokeJwtSecret());
    }

    public function testJwtSecretFallsBackToAppKeyHashedWhenJwtSecretUnset(): void
    {
        $appKey = str_repeat('C', 40);
        putenv('APP_KEY='.$appKey);

        $result = $this->invokeJwtSecret();

        self::assertSame(hash('sha256', $appKey, binary: true), $result);
        self::assertSame(32, strlen($result));
    }
}
