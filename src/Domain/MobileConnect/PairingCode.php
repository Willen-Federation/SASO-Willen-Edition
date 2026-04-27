<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Short-lived token embedded in the QR code displayed by the web UI.
 *
 * Flow: an admin generates a PairingCode → the server encodes it into a
 * `saso://connect?token=<code>&url=<server>` deep-link URI → the Flutter
 * app scans the QR, extracts the URI, and calls POST /api/v1/mobile/connect.
 * On success the code is marked `used` and a long-lived DeviceToken is
 * issued in its place.
 *
 * The raw token is a URL-safe base64 string of 32 random bytes (256-bit
 * entropy). Only the raw form leaves the server (inside the QR); the DB
 * stores only the SHA-256 hash so a DB leak cannot be replayed.
 */
final readonly class PairingCode
{
    public const TTL_MINUTES = 10;

    public function __construct(
        public int $id,
        public string $tokenHash,
        public string $label,
        public bool $used,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('PairingCode.id must be a positive integer.');
        }
        if ($tokenHash === '') {
            throw new InvalidArgumentException('PairingCode.tokenHash must not be empty.');
        }
        if ($label === '') {
            throw new InvalidArgumentException('PairingCode.label must not be empty.');
        }
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now > $this->expiresAt;
    }

    public function markUsed(): self
    {
        return new self(
            id: $this->id,
            tokenHash: $this->tokenHash,
            label: $this->label,
            used: true,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public static function generateRawToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
