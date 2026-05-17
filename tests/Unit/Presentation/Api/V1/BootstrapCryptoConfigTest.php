<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Saso\Presentation\Api\V1\Bootstrap;

/**
 * Regression coverage for GitHub issue #205 — the API Bootstrap must fail
 * closed when APP_KEY / JWT_SECRET are unset or too short, instead of
 * silently falling back to an all-zero AES key or a DSN-derived JWT secret.
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

    private function invoke(string $method): mixed
    {
        $reflection = new ReflectionMethod(Bootstrap::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null);
    }

    public function testEncryptorKeyThrowsWhenAppKeyUnset(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY');

        $this->invoke('encryptorKey');
    }

    public function testEncryptorKeyThrowsWhenAppKeyTooShort(): void
    {
        putenv('APP_KEY=short');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('all-zero AES key');

        $this->invoke('encryptorKey');
    }

    public function testEncryptorKeyAcceptsBase64EncodedKey(): void
    {
        $raw       = random_bytes(32);
        $base64Key = base64_encode($raw);
        putenv('APP_KEY='.$base64Key);

        $result = $this->invoke('encryptorKey');

        self::assertIsString($result);
        self::assertSame(32, strlen($result));
        self::assertSame($raw, $result);
    }

    public function testEncryptorKeyAcceptsHexEncodedKey(): void
    {
        $raw    = random_bytes(32);
        $hexKey = bin2hex($raw);
        putenv('APP_KEY='.$hexKey);

        $result = $this->invoke('encryptorKey');

        self::assertIsString($result);
        self::assertSame(32, strlen($result));
        self::assertSame($raw, $result);
    }

    public function testEncryptorKeyHashesArbitraryLongString(): void
    {
        $arbitrary = str_repeat('z', 40);
        putenv('APP_KEY='.$arbitrary);

        $result = $this->invoke('encryptorKey');

        self::assertIsString($result);
        self::assertSame(32, strlen($result));
        self::assertSame(hash('sha256', $arbitrary, binary: true), $result);
    }

    public function testJwtSecretThrowsWhenNeitherSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT_SECRET');

        $this->invoke('jwtSecret');
    }

    public function testJwtSecretThrowsWhenBothTooShort(): void
    {
        putenv('JWT_SECRET=tooShort');
        putenv('APP_KEY=alsoShort');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('insecure fallback');

        $this->invoke('jwtSecret');
    }

    public function testJwtSecretPrefersJwtSecretOverAppKey(): void
    {
        $jwtSecret = str_repeat('A', 32);
        $appKey    = str_repeat('B', 32);
        putenv('JWT_SECRET='.$jwtSecret);
        putenv('APP_KEY='.$appKey);

        $result = $this->invoke('jwtSecret');

        self::assertSame($jwtSecret, $result);
    }

    public function testJwtSecretFallsBackToAppKeyHashedWhenJwtSecretUnset(): void
    {
        $appKey = str_repeat('C', 40);
        putenv('APP_KEY='.$appKey);

        $result = $this->invoke('jwtSecret');

        self::assertSame(hash('sha256', $appKey, binary: true), $result);
        self::assertSame(32, strlen($result));
    }
}
