<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Setting;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Setting\Exception\SettingNotFoundException;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingType;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

/**
 * Exercises the PdoSystemSettingService against a fresh SQLite
 * in-memory database. SQLite is a portable proxy for the test plane —
 * the SQL the service issues is intentionally written to work on both
 * SQLite and MariaDB. The MariaDB-specific schema (ENUM column,
 * BOOLEAN type, BLOB sub-types) is verified separately by the M4
 * Phinx migrations once the integration suite lands.
 */
final class PdoSystemSettingServiceTest extends TestCase
{
    private PDO $pdo;
    private SecretEncryptor $encryptor;
    private PdoSystemSettingService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE system_setting (
                "key"      TEXT PRIMARY KEY,
                value      BLOB NOT NULL,
                value_type TEXT NOT NULL,
                encrypted  INTEGER NOT NULL DEFAULT 0,
                updated_at TEXT NOT NULL,
                updated_by TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE system_setting_audit (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                "key"       TEXT NOT NULL,
                old_value   BLOB,
                new_value   BLOB,
                changed_by  TEXT NOT NULL,
                changed_at  TEXT NOT NULL,
                reason      TEXT
            )',
        );

        $this->encryptor = new SecretEncryptor(SecretEncryptor::generateKey());
        $this->service   = new PdoSystemSettingService($this->pdo, $this->encryptor);
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        self::assertNull($this->service->get(new SettingKey('unknown')));
    }

    public function testRequireThrowsForUnknownKey(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->expectExceptionMessage('No system_setting row found for key "unknown"');

        $this->service->require(new SettingKey('unknown'));
    }

    public function testSetThenGetRoundTripsString(): void
    {
        $this->service->set(
            new SettingKey('default_locale'),
            SettingValue::string('ja'),
            'admin-1',
        );

        $value = $this->service->get(new SettingKey('default_locale'));
        self::assertNotNull($value);
        self::assertSame(SettingType::String, $value->type);
        self::assertSame('ja', $value->asString());
    }

    public function testSetUpdatesExistingRow(): void
    {
        $key = new SettingKey('default_locale');
        $this->service->set($key, SettingValue::string('en'), 'admin-1');
        $this->service->set($key, SettingValue::string('ja'), 'admin-2', reason: 'Switching back to JA');

        $value = $this->service->require($key);
        self::assertSame('ja', $value->asString());

        // The single row should be updated, not duplicated.
        self::assertSame(
            1,
            (int) $this->scalar('SELECT COUNT(*) FROM system_setting WHERE "key" = "default_locale"'),
        );
    }

    public function testRoundTripsAllNonSecretTypes(): void
    {
        $cases = [
            'a_string' => SettingValue::string('hello'),
            'an_int'   => SettingValue::int(42),
            'a_bool'   => SettingValue::bool(true),
            'a_json'   => SettingValue::json(['enabled' => true, 'modes' => ['oidc', 'saml']]),
        ];

        foreach ($cases as $keyName => $written) {
            $this->service->set(new SettingKey($keyName), $written, 'admin');
        }

        foreach ($cases as $keyName => $written) {
            $read = $this->service->require(new SettingKey($keyName));
            self::assertSame($written->type, $read->type, $keyName);
            self::assertSame($written->raw, $read->raw, $keyName);
        }
    }

    public function testSecretIsEncryptedAtRest(): void
    {
        $this->service->set(
            new SettingKey('oidc.client_secret'),
            SettingValue::secret('topsecret'),
            'admin',
        );

        // Stored bytes must NOT match the plaintext.
        $rawStored = (string) $this->scalar(
            'SELECT value FROM system_setting WHERE "key" = "oidc.client_secret"',
        );
        self::assertNotSame('topsecret', $rawStored);

        // But the service decrypts transparently on read.
        $value = $this->service->require(new SettingKey('oidc.client_secret'));
        self::assertSame(SettingType::Secret, $value->type);
        self::assertSame('topsecret', $value->asString());
    }

    public function testWriteEmitsAuditRowWithCorrectShape(): void
    {
        $key = new SettingKey('default_locale');
        $this->service->set($key, SettingValue::string('en'), 'admin-1');
        $this->service->set($key, SettingValue::string('ja'), 'admin-2', reason: 'switch');

        $rows = $this->fetchAll(
            'SELECT * FROM system_setting_audit WHERE "key" = "default_locale" ORDER BY id ASC',
        );
        self::assertCount(2, $rows);

        // First row: insert (old_value null).
        self::assertNull($rows[0]['old_value']);
        self::assertSame('en', $rows[0]['new_value']);
        self::assertSame('admin-1', $rows[0]['changed_by']);

        // Second row: update.
        self::assertSame('en', $rows[1]['old_value']);
        self::assertSame('ja', $rows[1]['new_value']);
        self::assertSame('admin-2', $rows[1]['changed_by']);
        self::assertSame('switch', $rows[1]['reason']);
    }

    public function testAuditStoresCiphertextNotPlaintextForSecrets(): void
    {
        $this->service->set(
            new SettingKey('oidc.client_secret'),
            SettingValue::secret('topsecret'),
            'admin',
        );

        $newValue = (string) $this->scalar(
            'SELECT new_value FROM system_setting_audit WHERE "key" = "oidc.client_secret"',
        );
        self::assertNotSame('topsecret', $newValue);
    }

    public function testDeleteRemovesRowAndAuditsTheChange(): void
    {
        $key = new SettingKey('default_locale');
        $this->service->set($key, SettingValue::string('ja'), 'admin');
        $this->service->delete($key, 'admin', 'no longer needed');

        self::assertNull($this->service->get($key));

        $rows = $this->fetchAll(
            'SELECT * FROM system_setting_audit WHERE "key" = "default_locale" ORDER BY id ASC',
        );
        self::assertCount(2, $rows);
        self::assertNull($rows[1]['new_value']);
        self::assertSame('no longer needed', $rows[1]['reason']);
    }

    public function testDeleteOnUnknownKeyIsNoop(): void
    {
        // Should not throw and should not write an audit row.
        $this->service->delete(new SettingKey('never_existed'), 'admin');

        self::assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM system_setting_audit'));
    }

    public function testAllReturnsEveryRowKeyedByName(): void
    {
        $this->service->set(new SettingKey('default_locale'), SettingValue::string('ja'), 'admin');
        $this->service->set(new SettingKey('label.rows'), SettingValue::int(7), 'admin');

        $all = $this->service->all();

        self::assertCount(2, $all);
        self::assertArrayHasKey('default_locale', $all);
        self::assertSame('ja', $all['default_locale']->asString());
        self::assertSame(7, $all['label.rows']->asInt());
    }

    public function testRequestScopedCacheAvoidsRepeatedQueries(): void
    {
        $key = new SettingKey('default_locale');
        $this->service->set($key, SettingValue::string('en'), 'admin');

        // Read once to populate cache, then mutate the row directly via a
        // sibling PDO connection — the service must still serve the cached
        // value because it owns the cache for this request.
        $this->service->get($key);

        $this->pdo->exec(
            'UPDATE system_setting SET value = "ja" WHERE "key" = "default_locale"',
        );

        self::assertSame('en', $this->service->require($key)->asString());
    }

    public function testWriteInvalidatesTheCachedKey(): void
    {
        $key = new SettingKey('default_locale');
        $this->service->set($key, SettingValue::string('en'), 'admin');
        $this->service->get($key);

        $this->service->set($key, SettingValue::string('ja'), 'admin');

        self::assertSame('ja', $this->service->require($key)->asString());
    }

    public function testCacheAvoidsRefetchingMissingKey(): void
    {
        $key = new SettingKey('never_existed');

        self::assertNull($this->service->get($key));
        // A subsequent get after a sibling INSERT should still return the
        // cached `null` because we proved absence in this request.
        $this->pdo->exec(
            'INSERT INTO system_setting ("key", value, value_type, encrypted, updated_at, updated_by) '.
            'VALUES ("never_existed", "ja", "string", 0, "2026-01-01 00:00:00", "outsider")',
        );

        self::assertNull($this->service->get($key));
    }

    private function scalar(string $sql): mixed
    {
        $stmt = $this->pdo->query($sql);
        self::assertInstanceOf(\PDOStatement::class, $stmt);

        return $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAll(string $sql): array
    {
        $stmt = $this->pdo->query($sql);
        self::assertInstanceOf(\PDOStatement::class, $stmt);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
