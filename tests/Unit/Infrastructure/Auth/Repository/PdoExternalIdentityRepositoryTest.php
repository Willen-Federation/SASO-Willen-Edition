<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Repository;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\ExternalIdentity;
use Saso\Infrastructure\Auth\Repository\PdoExternalIdentityRepository;

final class PdoExternalIdentityRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoExternalIdentityRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE member_external_identity (
                member_id          INTEGER NOT NULL,
                auth_provider_id   INTEGER NOT NULL,
                external_subject   TEXT NOT NULL,
                created_at         TEXT NOT NULL,
                updated_at         TEXT NOT NULL,
                last_login_at      TEXT,
                PRIMARY KEY (auth_provider_id, external_subject)
            )',
        );

        $this->repo = new PdoExternalIdentityRepository($this->pdo);
    }

    public function testFindReturnsNullForUnknownIdentity(): void
    {
        self::assertNull($this->repo->find(new AuthProviderId(1), 'nope'));
    }

    public function testLinkThenFindRoundTrips(): void
    {
        $identity = $this->makeIdentity(memberId: 42, providerId: 1, sub: 'auth0|abc');
        $this->repo->link($identity);

        $found = $this->repo->find(new AuthProviderId(1), 'auth0|abc');
        self::assertNotNull($found);
        self::assertSame(42, $found->memberId);
        self::assertSame('auth0|abc', $found->externalSubject);
    }

    public function testListForMemberReturnsAllLinks(): void
    {
        $this->repo->link($this->makeIdentity(memberId: 1, providerId: 1, sub: 'idp1|sub'));
        $this->repo->link($this->makeIdentity(memberId: 1, providerId: 2, sub: 'idp2|sub'));
        $this->repo->link($this->makeIdentity(memberId: 99, providerId: 1, sub: 'unrelated'));

        $list = $this->repo->listForMember(1);

        self::assertCount(2, $list);
        self::assertSame(
            [1, 2],
            array_map(static fn (ExternalIdentity $i): int => $i->authProviderId->value, $list),
        );
    }

    public function testRecordLoginUpdatesLastLoginAt(): void
    {
        $this->repo->link($this->makeIdentity(memberId: 1, providerId: 1, sub: 's', lastLogin: null));
        $this->repo->recordLogin(new AuthProviderId(1), 's');

        $found = $this->repo->find(new AuthProviderId(1), 's');
        self::assertNotNull($found);
        self::assertNotNull($found->lastLoginAt);
    }

    public function testUnlinkRemovesTheRow(): void
    {
        $this->repo->link($this->makeIdentity(memberId: 1, providerId: 1, sub: 's'));
        $this->repo->unlink(new AuthProviderId(1), 's');

        self::assertNull($this->repo->find(new AuthProviderId(1), 's'));
    }

    public function testCompositePrimaryKeyEnforced(): void
    {
        // Same (provider_id, external_subject) — second link must raise.
        $this->repo->link($this->makeIdentity(memberId: 1, providerId: 1, sub: 's'));

        $this->expectException(\PDOException::class);

        $this->repo->link($this->makeIdentity(memberId: 2, providerId: 1, sub: 's'));
    }

    private function makeIdentity(
        int $memberId,
        int $providerId,
        string $sub,
        ?DateTimeImmutable $lastLogin = null,
    ): ExternalIdentity {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');

        return new ExternalIdentity(
            memberId: $memberId,
            authProviderId: new AuthProviderId($providerId),
            externalSubject: $sub,
            createdAt: $now,
            updatedAt: $now,
            lastLoginAt: $lastLogin,
        );
    }
}
