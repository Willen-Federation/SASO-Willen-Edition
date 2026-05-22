<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\MyPage\Passkey;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Application\MyPage\Passkey\Auth0ProviderLookup;

final class Auth0ProviderLookupTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE auth_provider (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                issuer_or_metadata_url TEXT,
                claim_mapping TEXT,
                enabled INTEGER NOT NULL DEFAULT 1
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE member_external_identity (
                member_id TEXT NOT NULL,
                auth_provider_id INTEGER NOT NULL,
                external_subject TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                last_login_at TEXT
            )',
        );
    }

    public function testReturnsNullForUnlinkedMember(): void
    {
        $lookup = new Auth0ProviderLookup($this->pdo);
        self::assertNull($lookup->findFor('lonely_user'));
    }

    public function testReturnsNullForEmptyMemberId(): void
    {
        $lookup = new Auth0ProviderLookup($this->pdo);
        self::assertNull($lookup->findFor(''));
    }

    public function testFindsAuth0LinkByFlavorMarker(): void
    {
        $this->insertProvider(1, 'oidc', null, json_encode(['_config' => ['flavor' => 'auth0']]));
        $this->insertIdentity('alice_001', 1, 'auth0|alice');

        $lookup = new Auth0ProviderLookup($this->pdo);
        $link   = $lookup->findFor('alice_001');

        self::assertNotNull($link);
        self::assertSame(1, $link->providerId->value);
        self::assertSame('auth0|alice', $link->externalSubject);
    }

    public function testFindsAuth0LinkByAuth0ComIssuerWhenFlavorMissing(): void
    {
        $this->insertProvider(2, 'oidc', 'https://tenant.eu.auth0.com/', null);
        $this->insertIdentity('bob_002', 2, 'auth0|bob');

        $lookup = new Auth0ProviderLookup($this->pdo);
        $link   = $lookup->findFor('bob_002');

        self::assertNotNull($link);
        self::assertSame(2, $link->providerId->value);
    }

    public function testIgnoresNonAuth0OidcRows(): void
    {
        $this->insertProvider(3, 'oidc', 'https://accounts.google.com/', null);
        $this->insertIdentity('carol_003', 3, 'google|carol');

        $lookup = new Auth0ProviderLookup($this->pdo);
        self::assertNull($lookup->findFor('carol_003'));
    }

    public function testSkipsDisabledProviders(): void
    {
        $this->pdo->exec(
            "INSERT INTO auth_provider (id, name, type, issuer_or_metadata_url, claim_mapping, enabled)
             VALUES (4, 'Auth0', 'oidc', 'https://tenant.auth0.com/', NULL, 0)",
        );
        $this->insertIdentity('dave_004', 4, 'auth0|dave');

        $lookup = new Auth0ProviderLookup($this->pdo);
        self::assertNull($lookup->findFor('dave_004'));
    }

    public function testPrefersMostRecentlyUsedAuth0LinkWhenMultiple(): void
    {
        $this->insertProvider(5, 'oidc', 'https://t1.auth0.com/', null);
        $this->insertProvider(6, 'oidc', 'https://t2.auth0.com/', null);
        $this->insertIdentity('eve_005', 5, 'auth0|t1', lastLoginAt: '2026-04-01 00:00:00');
        $this->insertIdentity('eve_005', 6, 'auth0|t2', lastLoginAt: '2026-05-10 00:00:00');

        $lookup = new Auth0ProviderLookup($this->pdo);
        $link   = $lookup->findFor('eve_005');

        self::assertNotNull($link);
        self::assertSame(6, $link->providerId->value, 'most recent last_login_at should win');
    }

    private function insertProvider(int $id, string $type, ?string $issuer, ?string $claimMapping): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO auth_provider (id, name, type, issuer_or_metadata_url, claim_mapping, enabled)
             VALUES (:id, :name, :type, :url, :cm, 1)',
        );
        $stmt->execute([
            ':id'   => $id,
            ':name' => 'Provider'.$id,
            ':type' => $type,
            ':url'  => $issuer,
            ':cm'   => $claimMapping,
        ]);
    }

    private function insertIdentity(
        string $memberId,
        int $providerId,
        string $sub,
        string $lastLoginAt = '2026-05-23 00:00:00',
    ): void {
        $this->pdo->prepare(
            'INSERT INTO member_external_identity
             (member_id, auth_provider_id, external_subject, created_at, updated_at, last_login_at)
             VALUES (:mid, :pid, :sub, :now, :now, :ll)',
        )->execute([
            ':mid' => $memberId,
            ':pid' => $providerId,
            ':sub' => $sub,
            ':now' => '2026-01-01 00:00:00',
            ':ll'  => $lastLoginAt,
        ]);
    }
}
