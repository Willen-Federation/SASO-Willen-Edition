<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Long-lived refresh-token record for a paired Flutter device.
 *
 * After the JWT switch the device_token row acts as the refresh-token
 * record. The short-lived JWT access token (1 h) is stateless (never
 * stored). The opaque refresh token (1 year) is stored only as a
 * SHA-256 hash in `refreshTokenHash`; the raw value is returned once
 * at issuance and never re-read from the DB.
 *
 * `token_hash` retains the original pairing exchange hash for backward
 * compatibility. New code uses `refreshTokenHash` exclusively for the
 * OAuth2 refresh grant.
 *
 * Expiry defaults to one year from issuance. Admins may revoke a token
 * at any time via DELETE /api/v1/mobile/tokens/{id}.
 */
final readonly class DeviceToken
{
    public const TTL_DAYS = 365;

    public function __construct(
        public int $id,
        public string $tokenHash,
        public ?string $refreshTokenHash,
        public string $deviceName,
        public bool $revoked,
        public ?DateTimeImmutable $lastUsedAt,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
    ) {
        if ($id < 1) {
            throw new InvalidArgumentException('DeviceToken.id must be a positive integer.');
        }
        if ($tokenHash === '') {
            throw new InvalidArgumentException('DeviceToken.tokenHash must not be empty.');
        }
        if ($deviceName === '') {
            throw new InvalidArgumentException('DeviceToken.deviceName must not be empty.');
        }
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now > $this->expiresAt;
    }

    public function revoke(): self
    {
        return new self(
            id: $this->id,
            tokenHash: $this->tokenHash,
            refreshTokenHash: $this->refreshTokenHash,
            deviceName: $this->deviceName,
            revoked: true,
            lastUsedAt: $this->lastUsedAt,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
        );
    }

    public function withLastUsed(DateTimeImmutable $at): self
    {
        return new self(
            id: $this->id,
            tokenHash: $this->tokenHash,
            refreshTokenHash: $this->refreshTokenHash,
            deviceName: $this->deviceName,
            revoked: $this->revoked,
            lastUsedAt: $at,
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
