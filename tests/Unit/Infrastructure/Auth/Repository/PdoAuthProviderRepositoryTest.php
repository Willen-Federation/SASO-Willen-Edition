<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Repository;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;

final class PdoAuthProviderRepositoryTest extends TestCase
{
    private PDO $pdo;
    private SecretEncryptor $encryptor;
    private PdoAuthProviderRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE auth_provider (
                id                       INTEGER PRIMARY KEY,
                name                     TEXT NOT NULL,
                type                     TEXT NOT NULL,
                issuer_or_metadata_url   TEXT,
                client_id                TEXT,
                client_secret_encrypted  BLOB,
                scopes                   TEXT,
                claim_mapping            TEXT,
                enabled                  INTEGER NOT NULL DEFAULT 0,
                is_default               INTEGER NOT NULL DEFAULT 0,
                created_at               TEXT NOT NULL,
                updated_at               TEXT NOT NULL
            )',
        );

        $this->encryptor = new SecretEncryptor(SecretEncryptor::generateKey());
        $this->repo      = new PdoAuthProviderRepository($this->pdo, $this->encryptor);
    }

    public function testFindByIdReturnsNullForUnknownRow(): void
    {
        self::assertNull($this->repo->findById(new AuthProviderId(99)));
    }

    public function testSaveInsertsThenFindRoundTrips(): void
    {
        $record = $this->makeRecord(id: 1, name: 'Auth0 staff', secret: 'topsecret', enabled: true);

        $saved = $this->repo->save($record);
        self::assertSame('Auth0 staff', $saved->name);
        self::assertSame('topsecret', $saved->clientSecret);

        $reread = $this->repo->findById(new AuthProviderId(1));
        self::assertNotNull($reread);
        self::assertSame('Auth0 staff', $reread->name);
        self::assertSame(AuthProviderType::Oidc, $reread->type);
        self::assertSame('topsecret', $reread->clientSecret);
        self::assertTrue($reread->enabled);
    }

    public function testSecretIsEncryptedAtRest(): void
    {
        $this->repo->save($this->makeRecord(id: 1, secret: 'topsecret'));

        $stmt = $this->pdo->query('SELECT client_secret_encrypted FROM auth_provider WHERE id = 1');
        self::assertInstanceOf(\PDOStatement::class, $stmt);
        $cipher = (string) $stmt->fetchColumn();

        self::assertNotSame('topsecret', $cipher);
        self::assertSame("\x01", $cipher[0]);
    }

    public function testNullSecretRoundTripsAsNull(): void
    {
        $this->repo->save($this->makeRecord(id: 1, secret: null));

        $reread = $this->repo->findById(new AuthProviderId(1));
        self::assertNotNull($reread);
        self::assertNull($reread->clientSecret);
    }

    public function testSaveUpdatesExistingRow(): void
    {
        $this->repo->save($this->makeRecord(id: 1, name: 'first', enabled: true));
        $this->repo->save($this->makeRecord(id: 1, name: 'second', enabled: false));

        $reread = $this->repo->findById(new AuthProviderId(1));
        self::assertNotNull($reread);
        self::assertSame('second', $reread->name);
        self::assertFalse($reread->enabled);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM auth_provider');
        self::assertInstanceOf(\PDOStatement::class, $stmt);
        self::assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testListAllReturnsDefaultFirstThenAlphaByName(): void
    {
        $this->repo->save($this->makeRecord(id: 1, name: 'Zebra', isDefault: false));
        $this->repo->save($this->makeRecord(id: 2, name: 'Alpha', isDefault: false));
        $this->repo->save($this->makeRecord(id: 3, name: 'Featured', isDefault: true));

        $list = $this->repo->listAll();

        self::assertSame(['Featured', 'Alpha', 'Zebra'], array_map(
            static fn (AuthProviderRecord $r): string => $r->name,
            $list,
        ));
    }

    public function testListEnabledFiltersDisabledRows(): void
    {
        $this->repo->save($this->makeRecord(id: 1, name: 'A', enabled: true));
        $this->repo->save($this->makeRecord(id: 2, name: 'B', enabled: false));
        $this->repo->save($this->makeRecord(id: 3, name: 'C', enabled: true));

        $list = $this->repo->listEnabled();

        self::assertSame(['A', 'C'], array_map(
            static fn (AuthProviderRecord $r): string => $r->name,
            $list,
        ));
    }

    public function testClaimMappingRoundTrips(): void
    {
        $mapping = ['display_name' => 'preferred_username', 'roles' => 'cognito:groups'];
        $this->repo->save($this->makeRecord(id: 1, claimMapping: $mapping));

        $reread = $this->repo->findById(new AuthProviderId(1));
        self::assertNotNull($reread);
        self::assertSame($mapping, $reread->claimMapping);
    }

    public function testDeleteRemovesTheRow(): void
    {
        $this->repo->save($this->makeRecord(id: 1));
        $this->repo->delete(new AuthProviderId(1));

        self::assertNull($this->repo->findById(new AuthProviderId(1)));
    }

    /**
     * @param array<string, string>|null $claimMapping
     */
    private function makeRecord(
        int $id,
        string $name = 'Test Provider',
        ?string $secret = 'a-secret',
        bool $enabled = false,
        bool $isDefault = false,
        ?array $claimMapping = null,
    ): AuthProviderRecord {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');

        return new AuthProviderRecord(
            id: new AuthProviderId($id),
            name: $name,
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: 'https://example.test/.well-known/openid-configuration',
            clientId: 'client-id-'.$id,
            clientSecret: $secret,
            scopes: 'openid email profile',
            claimMapping: $claimMapping,
            enabled: $enabled,
            isDefault: $isDefault,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
