<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\MobileConnect;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\MobileConnect\PairingCode;

final class PairingCodeTest extends TestCase
{
    public function testStoresAllFields(): void
    {
        $now    = new DateTimeImmutable('2026-04-26 12:00:00');
        $expiry = new DateTimeImmutable('2026-04-26 12:10:00');
        $code   = new PairingCode(
            id: 1,
            tokenHash: str_repeat('a', 64),
            label: 'Test pairing',
            used: false,
            expiresAt: $expiry,
            createdAt: $now,
        );

        self::assertSame(1, $code->id);
        self::assertFalse($code->used);
        self::assertSame('Test pairing', $code->label);
        self::assertSame($expiry, $code->expiresAt);
    }

    public function testIsExpiredReturnsFalseBeforeExpiry(): void
    {
        $now    = new DateTimeImmutable('2026-04-26 12:00:00');
        $expiry = new DateTimeImmutable('2026-04-26 12:10:00');
        $code   = $this->makeCode(expiry: $expiry);

        self::assertFalse($code->isExpired($now));
    }

    public function testIsExpiredReturnsTrueAfterExpiry(): void
    {
        $expiry = new DateTimeImmutable('2026-04-26 12:00:00');
        $after  = new DateTimeImmutable('2026-04-26 12:01:00');
        $code   = $this->makeCode(expiry: $expiry);

        self::assertTrue($code->isExpired($after));
    }

    public function testMarkUsedIsNonMutating(): void
    {
        $code  = $this->makeCode(used: false);
        $used  = $code->markUsed();

        self::assertNotSame($code, $used);
        self::assertFalse($code->used);
        self::assertTrue($used->used);
    }

    public function testHashTokenProducesSha256Hex(): void
    {
        $hash = PairingCode::hashToken('hello');

        self::assertSame(64, strlen($hash));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function testGenerateRawTokenIsUrlSafe(): void
    {
        $token = PairingCode::generateRawToken();

        self::assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
        self::assertGreaterThanOrEqual(40, strlen($token));
    }

    public function testRejectsIdZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->makeCode(id: 0);
    }

    public function testRejectsEmptyHash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PairingCode(
            id: 1,
            tokenHash: '',
            label: 'test',
            used: false,
            expiresAt: new DateTimeImmutable('+10 minutes'),
            createdAt: new DateTimeImmutable(),
        );
    }

    public function testRejectsEmptyLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PairingCode(
            id: 1,
            tokenHash: str_repeat('a', 64),
            label: '',
            used: false,
            expiresAt: new DateTimeImmutable('+10 minutes'),
            createdAt: new DateTimeImmutable(),
        );
    }

    private function makeCode(
        int $id = 1,
        bool $used = false,
        ?DateTimeImmutable $expiry = null,
    ): PairingCode {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');

        return new PairingCode(
            id: $id,
            tokenHash: str_repeat('a', 64),
            label: 'Test',
            used: $used,
            expiresAt: $expiry ?? new DateTimeImmutable('2026-04-26 12:10:00'),
            createdAt: $now,
        );
    }
}
