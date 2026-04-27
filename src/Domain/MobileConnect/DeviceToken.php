<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Long-lived API credential for a paired Flutter device.
 *
 * Issued after a successful pairing-code exchange. The device presents
 * this token as `Authorization: Bearer <rawToken>` on subsequent API
 * calls. The DB stores only the SHA-256 hash; the raw token is returned
 * once at issuance and never stored in plaintext.
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
