<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Crypto;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class SecretEncryptorTest extends TestCase
{
    private SecretEncryptor $enc;
    private string $key;

    protected function setUp(): void
    {
        $this->key = SecretEncryptor::generateKey();
        $this->enc = new SecretEncryptor($this->key);
    }

    public function testRoundTripPreservesPlaintext(): void
    {
        $plaintext = 'oidc-client-secret-with-some-bytes-✓';

        $bundle = $this->enc->encrypt($plaintext);

        self::assertNotSame($plaintext, $bundle);
        self::assertSame($plaintext, $this->enc->decrypt($bundle));
    }

    public function testCiphertextStartsWithVersionByte(): void
    {
        $bundle = $this->enc->encrypt('x');

        self::assertSame("\x01", $bundle[0]);
    }

    public function testTwoEncryptionsOfSamePlaintextDifferOnIv(): void
    {
        $a = $this->enc->encrypt('same plaintext');
        $b = $this->enc->encrypt('same plaintext');

        self::assertNotSame($a, $b);
    }

    public function testTamperedCiphertextFailsAuthentication(): void
    {
        $bundle = $this->enc->encrypt('secret');

        // Flip a byte in the ciphertext segment (after the version + IV).
        $bundle[1 + 12] = chr(ord($bundle[1 + 12]) ^ 0x01);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('authentication tag did not validate');

        $this->enc->decrypt($bundle);
    }

    public function testTamperedTagFailsAuthentication(): void
    {
        $bundle = $this->enc->encrypt('secret');

        // Flip the last byte (tag).
        $bundle[strlen($bundle) - 1] = chr(ord($bundle[strlen($bundle) - 1]) ^ 0x01);

        $this->expectException(RuntimeException::class);

        $this->enc->decrypt($bundle);
    }

    public function testWrongKeyFailsAuthentication(): void
    {
        $bundle = $this->enc->encrypt('secret');

        $other = new SecretEncryptor(SecretEncryptor::generateKey());

        $this->expectException(RuntimeException::class);

        $other->decrypt($bundle);
    }

    public function testRejectsKeyOfWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be 32 bytes');

        new SecretEncryptor(str_repeat('a', 16));
    }

    public function testRejectsTruncatedBundle(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('truncated');

        $this->enc->decrypt("\x01abc");
    }

    public function testRejectsUnknownCiphertextVersion(): void
    {
        // Re-encrypt then flip the version byte.
        $bundle    = $this->enc->encrypt('secret');
        $bundle[0] = "\xFE";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unknown ciphertext version');

        $this->enc->decrypt($bundle);
    }

    public function testEmptyPlaintextRoundTrips(): void
    {
        $bundle = $this->enc->encrypt('');

        self::assertSame('', $this->enc->decrypt($bundle));
    }

    public function testGenerateKeyProduces32Bytes(): void
    {
        self::assertSame(32, strlen(SecretEncryptor::generateKey()));
    }

    public function testDifferentGeneratedKeysAreUnique(): void
    {
        $keys = [];
        for ($i = 0; $i < 50; ++$i) {
            $keys[] = SecretEncryptor::generateKey();
        }

        self::assertCount(50, array_unique($keys));
    }
}
