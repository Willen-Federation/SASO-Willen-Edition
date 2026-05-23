<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Crypto;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

/**
 * Regression coverage for the unified APP_KEY resolution that fixes
 * "SecretEncryptor: authentication tag did not validate" — pre-fix,
 * Bootstrap.php accepted base64 / hex / sha256, while admin DIContainers
 * only accepted base64 (silently falling back to 32 zero bytes), so a
 * hex APP_KEY produced two different ciphertext keys depending on the
 * code path that touched the secret.
 */
final class AppKeyResolverTest extends TestCase
{
    private ?string $savedAppKey;

    protected function setUp(): void
    {
        $value             = getenv('APP_KEY');
        $this->savedAppKey = $value === false ? null : $value;
        putenv('APP_KEY');
    }

    protected function tearDown(): void
    {
        if ($this->savedAppKey === null) {
            putenv('APP_KEY');
        } else {
            putenv('APP_KEY='.$this->savedAppKey);
        }
    }

    public function testTryResolveReturnsNullWhenAppKeyUnset(): void
    {
        self::assertNull(AppKeyResolver::tryResolve());
    }

    public function testTryResolveReturnsNullWhenAppKeyTooShort(): void
    {
        putenv('APP_KEY=short');
        self::assertNull(AppKeyResolver::tryResolve());
    }

    public function testResolveThrowsWhenAppKeyUnset(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_KEY');

        AppKeyResolver::resolve();
    }

    public function testResolveAcceptsBase64EncodedKey(): void
    {
        $raw = random_bytes(32);
        putenv('APP_KEY='.base64_encode($raw));

        self::assertSame($raw, AppKeyResolver::resolve());
    }

    public function testResolveAcceptsHexEncodedKey(): void
    {
        $raw = random_bytes(32);
        putenv('APP_KEY='.bin2hex($raw));

        self::assertSame($raw, AppKeyResolver::resolve());
    }

    public function testResolveHashesArbitraryLongString(): void
    {
        $arbitrary = str_repeat('z', 40);
        putenv('APP_KEY='.$arbitrary);

        self::assertSame(hash('sha256', $arbitrary, binary: true), AppKeyResolver::resolve());
    }

    /**
     * The pre-fix admin paths derived a 32-zero-byte key from a hex
     * APP_KEY, while Bootstrap derived the real bytes — round-tripping
     * a ciphertext between them produced "authentication tag did not
     * validate". This guards against that divergence reappearing.
     */
    public function testHexAndBase64AppKeysProduceIdenticalEncryptors(): void
    {
        $raw       = random_bytes(32);
        $base64Key = base64_encode($raw);
        $hexKey    = bin2hex($raw);

        putenv('APP_KEY='.$base64Key);
        $encryptorFromBase64 = AppKeyResolver::encryptor();
        $bundle              = $encryptorFromBase64->encrypt('round-trip payload');

        putenv('APP_KEY='.$hexKey);
        $encryptorFromHex = AppKeyResolver::encryptor();

        self::assertSame('round-trip payload', $encryptorFromHex->decrypt($bundle));
    }

    public function testTryEncryptorReturnsEncryptorWhenAppKeyValid(): void
    {
        putenv('APP_KEY='.base64_encode(random_bytes(32)));

        self::assertInstanceOf(SecretEncryptor::class, AppKeyResolver::tryEncryptor());
    }

    public function testTryEncryptorReturnsNullWhenAppKeyMissing(): void
    {
        self::assertNull(AppKeyResolver::tryEncryptor());
    }
}
