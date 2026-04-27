<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\MobileConnect;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Infrastructure\MobileConnect\PdoPairingCodeRepository;

final class PdoPairingCodeRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoPairingCodeRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE pairing_code (
                id         INTEGER PRIMARY KEY,
                token_hash TEXT NOT NULL UNIQUE,
                label      TEXT NOT NULL,
                used       INTEGER NOT NULL DEFAULT 0,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL
            )',
        );
        $this->repo = new PdoPairingCodeRepository($this->pdo, new DateTimeZone('UTC'));
    }

    public function testFindByHashReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findByTokenHash(str_repeat('x', 64)));
    }

    public function testSaveAndFindRoundTrip(): void
    {
        $code = $this->makeCode(hash: str_repeat('a', 64), used: false);
        $this->repo->save($code);

        $found = $this->repo->findByTokenHash(str_repeat('a', 64));
        self::assertNotNull($found);
        self::assertSame(1, $found->id);
        self::assertFalse($found->used);
        self::assertSame('Test Label', $found->label);
    }

    public function testMarkUsedPersists(): void
    {
        $code = $this->makeCode(hash: str_repeat('b', 64), used: false);
        $this->repo->save($code);
        $this->repo->save($code->markUsed());

        $found = $this->repo->findByTokenHash(str_repeat('b', 64));
        self::assertNotNull($found);
        self::assertTrue($found->used);
    }

    public function testDeleteExpiredRemovesOldRows(): void
    {
        $past   = new DateTimeImmutable('2020-01-01 00:00:00', new DateTimeZone('UTC'));
        $future = new DateTimeImmutable('2099-01-01 00:00:00', new DateTimeZone('UTC'));
        $now    = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $expired = new PairingCode(1, str_repeat('c', 64), 'Expired', false, $past, $now);
        $active  = new PairingCode(2, str_repeat('d', 64), 'Active', false, $future, $now);

        $this->repo->save($expired);
        $this->repo->save($active);

        $deleted = $this->repo->deleteExpired();

        self::assertSame(1, $deleted);
        self::assertNull($this->repo->findByTokenHash(str_repeat('c', 64)));
        self::assertNotNull($this->repo->findByTokenHash(str_repeat('d', 64)));
    }

    private function makeCode(string $hash, bool $used): PairingCode
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00', new DateTimeZone('UTC'));

        return new PairingCode(
            id: 1,
            tokenHash: $hash,
            label: 'Test Label',
            used: $used,
            expiresAt: new DateTimeImmutable('2026-04-26 12:10:00', new DateTimeZone('UTC')),
            createdAt: $now,
        );
    }
}
