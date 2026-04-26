<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Crypto;

use InvalidArgumentException;
use RuntimeException;

/**
 * AES-256-GCM authenticated encryption for OIDC client secrets and SAML
 * private keys at rest.
 *
 * Wire format of the ciphertext column:
 *
 *     [ 1-byte version | 12-byte IV | N-byte ciphertext | 16-byte tag ]
 *
 * The version byte lets us rotate the algorithm later (or the key
 * derivation) without bricking already-stored secrets — a future
 * `decrypt()` would dispatch on it.
 *
 * The key material is the application's `APP_KEY` (32 bytes, stored in
 * `.env` and never committed). Constructor accepts the raw 32 bytes;
 * upper layers parse `APP_KEY` from base64 / hex and feed it in.
 *
 * Choices not made here:
 *
 *   * No key rotation. M5 ships a key-rotation runbook; until then
 *     rotating means re-encrypting every row through a one-shot script.
 *   * No additional authenticated data (AAD). The row's primary key
 *     could serve as AAD to bind ciphertext to a row, but that adds
 *     coupling the M3 surface does not benefit from.
 *   * No envelope encryption (KMS / Vault). Self-hosted operators run
 *     this on shared hosting; a single per-process key is the right
 *     baseline.
 */
final class SecretEncryptor
{
    private const CIPHER     = 'aes-256-gcm';
    private const KEY_BYTES  = 32;
    private const IV_BYTES   = 12;
    private const TAG_BYTES  = 16;
    private const VERSION    = "\x01";

    public function __construct(
        private readonly string $key,
    ) {
        if (strlen($this->key) !== self::KEY_BYTES) {
            throw new InvalidArgumentException(sprintf(
                'SecretEncryptor key must be %d bytes (got %d).',
                self::KEY_BYTES,
                strlen($this->key),
            ));
        }
    }

    /**
     * Returns the binary ciphertext bundle. Callers persist the bytes as
     * a `VARBINARY` (or BYTEA) column; for transports that demand text,
     * base64-encode at the boundary.
     */
    public function encrypt(string $plaintext): string
    {
        $iv  = random_bytes(self::IV_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            tag_length: self::TAG_BYTES,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('SecretEncryptor: openssl_encrypt failed.');
        }

        return self::VERSION.$iv.$ciphertext.$tag;
    }

    public function decrypt(string $bundle): string
    {
        $minimumLength = 1 + self::IV_BYTES + self::TAG_BYTES;
        if (strlen($bundle) < $minimumLength) {
            throw new RuntimeException('SecretEncryptor: ciphertext bundle is truncated.');
        }

        $version = $bundle[0];
        if ($version !== self::VERSION) {
            throw new RuntimeException(sprintf(
                'SecretEncryptor: unknown ciphertext version 0x%02X.',
                ord($version),
            ));
        }

        $iv         = substr($bundle, 1, self::IV_BYTES);
        $tag        = substr($bundle, -self::TAG_BYTES);
        $ciphertext = substr($bundle, 1 + self::IV_BYTES, -self::TAG_BYTES);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($plaintext === false) {
            throw new RuntimeException(
                'SecretEncryptor: authentication tag did not validate. The ciphertext was tampered with, or the wrong APP_KEY is in use.',
            );
        }

        return $plaintext;
    }

    /**
     * Generates a fresh 32-byte key suitable for {@see __construct()}.
     * Operators run this once during installation and store the
     * resulting value (typically base64-encoded) as `APP_KEY` in `.env`.
     */
    public static function generateKey(): string
    {
        return random_bytes(self::KEY_BYTES);
    }
}
