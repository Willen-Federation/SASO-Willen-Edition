<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\MobileConnect;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\MobileConnect\DeviceToken;

final class DeviceTokenTest extends TestCase
{
    public function testStoresAllFields(): void
    {
        $now    = new DateTimeImmutable('2026-04-26 12:00:00');
        $expiry = new DateTimeImmutable('2027-04-26 12:00:00');
        $token  = new DeviceToken(
            id: 1,
            tokenHash: str_repeat('b', 64),
            refreshTokenHash: null,
            deviceName: 'iPad mini',
            revoked: false,
            lastUsedAt: null,
            expiresAt: $expiry,
            createdAt: $now,
        );

        self::assertSame(1, $token->id);
        self::assertSame('iPad mini', $token->deviceName);
        self::assertFalse($token->revoked);
        self::assertNull($token->lastUsedAt);
    }

    public function testIsExpiredReturnsFalseBeforeExpiry(): void
    {
        $now   = new DateTimeImmutable('2026-04-26 12:00:00');
        $token = $this->makeToken(expiry: new DateTimeImmutable('2027-04-26 12:00:00'));

        self::assertFalse($token->isExpired($now));
    }

    public function testIsExpiredReturnsTrueAfterExpiry(): void
    {
        $expiry = new DateTimeImmutable('2026-04-26 12:00:00');
        $after  = new DateTimeImmutable('2026-04-27 12:00:00');
        $token  = $this->makeToken(expiry: $expiry);

        self::assertTrue($token->isExpired($after));
    }

    public function testRevokeIsNonMutating(): void
    {
        $token   = $this->makeToken(revoked: false);
        $revoked = $token->revoke();

        self::assertNotSame($token, $revoked);
        self::assertFalse($token->revoked);
        self::assertTrue($revoked->revoked);
    }

    public function testWithLastUsedUpdatesTimestamp(): void
    {
        $token = $this->makeToken();
        $at    = new DateTimeImmutable('2026-05-01 08:00:00');
        $updated = $token->withLastUsed($at);

        self::assertNotSame($token, $updated);
        self::assertNull($token->lastUsedAt);
        self::assertSame($at, $updated->lastUsedAt);
    }

    public function testHashTokenProducesSha256Hex(): void
    {
        $hash = DeviceToken::hashToken('test_value');

        self::assertSame(64, strlen($hash));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function testGenerateRawTokenIsUrlSafe(): void
    {
        $token = DeviceToken::generateRawToken();

        self::assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
        self::assertGreaterThanOrEqual(40, strlen($token));
    }

    public function testRejectsIdZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeToken(id: 0);
    }

    public function testRejectsEmptyHash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DeviceToken(
            id: 1,
            tokenHash: '',
            refreshTokenHash: null,
            deviceName: 'device',
            revoked: false,
            lastUsedAt: null,
            expiresAt: new DateTimeImmutable('+1 year'),
            createdAt: new DateTimeImmutable(),
        );
    }

    public function testRejectsEmptyDeviceName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DeviceToken(
            id: 1,
            tokenHash: str_repeat('b', 64),
            refreshTokenHash: null,
            deviceName: '',
            revoked: false,
            lastUsedAt: null,
            expiresAt: new DateTimeImmutable('+1 year'),
            createdAt: new DateTimeImmutable(),
        );
    }

    private function makeToken(
        int $id = 1,
        bool $revoked = false,
        ?DateTimeImmutable $expiry = null,
    ): DeviceToken {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');

        return new DeviceToken(
            id: $id,
            tokenHash: str_repeat('b', 64),
            refreshTokenHash: null,
            deviceName: 'Test Device',
            revoked: $revoked,
            lastUsedAt: null,
            expiresAt: $expiry ?? new DateTimeImmutable('2027-04-26 12:00:00'),
            createdAt: $now,
        );
    }
}
