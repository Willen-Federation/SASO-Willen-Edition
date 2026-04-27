<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\MobileConnect;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;

final class PdoDeviceTokenRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoDeviceTokenRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE device_token (
                id                 INTEGER PRIMARY KEY,
                token_hash         TEXT NOT NULL UNIQUE,
                refresh_token_hash TEXT UNIQUE,
                device_name        TEXT NOT NULL,
                revoked            INTEGER NOT NULL DEFAULT 0,
                last_used_at       TEXT,
                expires_at         TEXT NOT NULL,
                created_at         TEXT NOT NULL
            )',
        );
        $this->repo = new PdoDeviceTokenRepository($this->pdo, new DateTimeZone('UTC'));
    }

    public function testFindByHashReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findByTokenHash(str_repeat('z', 64)));
    }

    public function testFindByIdReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findById(999));
    }

    public function testSaveAndFindByHashRoundTrip(): void
    {
        $token = $this->makeToken(hash: str_repeat('a', 64));
        $this->repo->save($token);

        $found = $this->repo->findByTokenHash(str_repeat('a', 64));
        self::assertNotNull($found);
        self::assertSame('My Phone', $found->deviceName);
        self::assertFalse($found->revoked);
    }

    public function testSaveAndFindByIdRoundTrip(): void
    {
        $token = $this->makeToken(hash: str_repeat('b', 64));
        $this->repo->save($token);

        $found = $this->repo->findById(1);
        self::assertNotNull($found);
        self::assertSame(1, $found->id);
    }

    public function testRevokePersists(): void
    {
        $token = $this->makeToken(hash: str_repeat('c', 64));
        $this->repo->save($token);
        $this->repo->save($token->revoke());

        $found = $this->repo->findById(1);
        self::assertNotNull($found);
        self::assertTrue($found->revoked);
    }

    public function testListAllReturnsAllRows(): void
    {
        $this->repo->save($this->makeToken(hash: str_repeat('d', 64), id: 1));
        $this->repo->save($this->makeToken(hash: str_repeat('e', 64), id: 2));

        $list = $this->repo->listAll();
        self::assertCount(2, $list);
    }

    private function makeToken(string $hash, int $id = 1): DeviceToken
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00', new DateTimeZone('UTC'));

        return new DeviceToken(
            id: $id,
            tokenHash: $hash,
            refreshTokenHash: null,
            deviceName: 'My Phone',
            revoked: false,
            lastUsedAt: null,
            expiresAt: new DateTimeImmutable('2027-04-26 12:00:00', new DateTimeZone('UTC')),
            createdAt: $now,
        );
    }
}
