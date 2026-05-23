<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Crypto;

use RuntimeException;

/**
 * Single source of truth for deriving the AES-256-GCM key used by
 * {@see SecretEncryptor} from the `APP_KEY` environment variable.
 *
 * Resolution order (identical across every call site):
 *   1. APP_KEY as base64-encoded 32 bytes (44 chars with padding)
 *   2. APP_KEY as hex-encoded 32 bytes (64 hex chars)
 *   3. APP_KEY as any string ≥ 32 chars, run through SHA-256
 *
 * Historically each DIContainer reimplemented this resolver, and the
 * legacy admin/settings paths only accepted the base64 form — falling
 * back silently to 32 zero bytes when APP_KEY was hex or string-form.
 * Boot-time code (`Bootstrap::encryptorKey()`) used the 3-way form, so
 * data encrypted in production could not be decrypted in the Firebase
 * settings page ("authentication tag did not validate"). Routing all
 * callers through this class eliminates the divergence.
 */
final class AppKeyResolver
{
    private const KEY_BYTES = 32;

    /**
     * Returns the 32-byte raw key suitable for {@see SecretEncryptor::__construct()}.
     *
     * @throws RuntimeException if APP_KEY is missing or shorter than 32 chars
     */
    public static function resolve(): string
    {
        $key = self::tryResolve();
        if ($key === null) {
            throw new RuntimeException(
                'APP_KEY must be set to a base64-encoded 32 bytes, hex-encoded 32 bytes, '
                .'or any string of at least 32 characters. Refusing to boot with an all-zero AES key. '
                .'See .env.example.',
            );
        }

        return $key;
    }

    /**
     * Same resolution as {@see resolve()} but returns null instead of throwing.
     *
     * Useful for admin views that want to render a friendly error page
     * instead of a 500 when the operator's `.env` is misconfigured.
     */
    public static function tryResolve(): ?string
    {
        $appKey = getenv('APP_KEY');
        if (!is_string($appKey) || $appKey === '') {
            return null;
        }

        $raw = base64_decode($appKey, strict: true);
        if ($raw !== false && strlen($raw) === self::KEY_BYTES) {
            return $raw;
        }

        if (preg_match('/^[0-9a-fA-F]{64}$/', $appKey)) {
            $hex = hex2bin($appKey);
            if ($hex !== false && strlen($hex) === self::KEY_BYTES) {
                return $hex;
            }
        }

        if (strlen($appKey) >= self::KEY_BYTES) {
            return hash('sha256', $appKey, binary: true);
        }

        return null;
    }

    /**
     * Convenience for the common pattern of "build a SecretEncryptor or
     * return null". Saves callers from constructing the encryptor manually.
     */
    public static function tryEncryptor(): ?SecretEncryptor
    {
        $key = self::tryResolve();

        return $key === null ? null : new SecretEncryptor($key);
    }

    /**
     * Strict counterpart of {@see tryEncryptor()}.
     *
     * @throws RuntimeException if APP_KEY is missing or shorter than 32 chars
     */
    public static function encryptor(): SecretEncryptor
    {
        return new SecretEncryptor(self::resolve());
    }
}
