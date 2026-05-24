<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\Controller\ReadinessController;
use Saso\Presentation\Api\V1\HttpRequest;

/**
 * Drives {@see ReadinessController} against an in-memory SQLite database
 * so the schema-introspection path is exercised without depending on a
 * live MySQL instance. The controller branches on the driver name to
 * pick the right column-discovery query; we verify both the
 * "everything present" and the "schema drifted" outcomes here.
 *
 * @see ReadinessController
 */
final class ReadinessControllerTest extends TestCase
{
    public function testReturns200AndReadyWhenAllChecksPass(): void
    {
        $pdo = $this->newPdoWithCompleteSchema();
        $controller = new ReadinessController(static fn (): PDO => $pdo);

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));

        self::assertSame(200, $response->status);
        self::assertSame('ready', $response->body['status']);
        self::assertNotEmpty($response->body['checks']);
        foreach ($response->body['checks'] as $check) {
            self::assertSame('ok', $check['status'], sprintf(
                'check "%s" expected ok, got %s (%s)',
                $check['name'],
                $check['status'],
                $check['detail'] ?? '',
            ));
        }
    }

    public function testReturns503AndDegradedWhenColumnMissing(): void
    {
        $pdo = $this->newPdoWithCompleteSchema();
        // Drift: drop a column the API requires. SQLite < 3.35 cannot
        // DROP COLUMN, so we rebuild the table without `note`.
        $pdo->exec('DROP TABLE item');
        $pdo->exec(
            'CREATE TABLE item ('
            .'id INTEGER PRIMARY KEY, name TEXT, jan_code TEXT, isbn TEXT, '
            .'label_code TEXT, status TEXT, storage_location_id INTEGER'
            .')',
        );

        $controller = new ReadinessController(static fn (): PDO => $pdo);
        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));

        self::assertSame(503, $response->status);
        self::assertSame('degraded', $response->body['status']);

        $missing = self::firstCheckNamed($response->body['checks'], 'schema.item.note');
        self::assertNotNull($missing, 'expected a schema.item.note entry');
        self::assertSame('missing', $missing['status']);
    }

    public function testReturns503AndDegradedWhenTableMissing(): void
    {
        $pdo = $this->newPdoWithCompleteSchema();
        $pdo->exec('DROP TABLE feature_flag');

        $controller = new ReadinessController(static fn (): PDO => $pdo);
        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));

        self::assertSame(503, $response->status);
        $entry = self::firstCheckNamed($response->body['checks'], 'schema.feature_flag');
        self::assertNotNull($entry);
        self::assertSame('missing', $entry['status']);
    }

    public function testReturns503AndDegradedWhenPdoFactoryThrows(): void
    {
        $controller = new ReadinessController(static function (): PDO {
            throw new PDOException('SQLSTATE[HY000] [2002] Connection refused');
        });

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));

        self::assertSame(503, $response->status);
        self::assertSame('degraded', $response->body['status']);

        $entry = self::firstCheckNamed($response->body['checks'], 'database.connect');
        self::assertNotNull($entry);
        self::assertSame('failed', $entry['status']);
        self::assertStringContainsString('Connection refused', $entry['detail']);
    }

    public function testRedactsCredentialsFromFactoryError(): void
    {
        $controller = new ReadinessController(static function (): PDO {
            throw new PDOException(
                'SQLSTATE[HY000] could not connect: user=root password=hunter2 dsn=mysql:host=db',
            );
        });

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));
        $entry = self::firstCheckNamed($response->body['checks'], 'database.connect');

        self::assertNotNull($entry);
        self::assertStringNotContainsString('hunter2', $entry['detail']);
        self::assertStringNotContainsString('root', $entry['detail']);
        self::assertStringContainsString('password=***', $entry['detail']);
        self::assertStringContainsString('user=***', $entry['detail']);
    }

    public function testRedactsDsnHostPortAndDbnameFromFactoryError(): void
    {
        // Real `PDO::__construct()` exceptions echo the DSN verbatim when
        // the server is unreachable; we must not return the host/port/db
        // tuple to anonymous orchestrator probes.
        $controller = new ReadinessController(static function (): PDO {
            throw new PDOException(
                'SQLSTATE[HY000] [2002] No such file or directory: '
                .'host=db.internal.lan;port=3306;dbname=saso_prod;unix_socket=/var/run/mysqld.sock',
            );
        });

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));
        $entry = self::firstCheckNamed($response->body['checks'], 'database.connect');

        self::assertNotNull($entry);
        self::assertStringNotContainsString('db.internal.lan', $entry['detail']);
        self::assertStringNotContainsString('3306', $entry['detail']);
        self::assertStringNotContainsString('saso_prod', $entry['detail']);
        self::assertStringNotContainsString('/var/run/mysqld.sock', $entry['detail']);
    }

    public function testRedactsMariaDbQuotedUserHostAndRawIp(): void
    {
        // The canonical "Access denied" message MariaDB / MySQL emit on a
        // failed connect exposes the application user and the source IP.
        $controller = new ReadinessController(static function (): PDO {
            throw new PDOException(
                "SQLSTATE[HY000] [1045] Access denied for user 'saso_app'@'10.0.0.42' (using password: YES)",
            );
        });

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));
        $entry = self::firstCheckNamed($response->body['checks'], 'database.connect');

        self::assertNotNull($entry);
        self::assertStringNotContainsString('saso_app', $entry['detail']);
        self::assertStringNotContainsString('10.0.0.42', $entry['detail']);
    }

    public function testProbesEveryRequiredSchemaTableEvenWhenOneIsMissing(): void
    {
        // Operators read the `checks` list to know which migration to run;
        // a single missing table must not short-circuit the rest of the
        // probe (otherwise multi-migration drift takes N redeploys to
        // diagnose).
        $pdo = $this->newPdoWithCompleteSchema();
        $pdo->exec('DROP TABLE pairing_code');

        $controller = new ReadinessController(static fn (): PDO => $pdo);
        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));

        self::assertSame(503, $response->status);
        // Every other table check still ran.
        foreach (['item', 'category', 'storage_location', 'auth_provider', 'feature_flag', 'device_token'] as $table) {
            $entry = self::firstCheckNamed($response->body['checks'], 'schema.'.$table);
            self::assertNotNull($entry, 'expected schema.'.$table.' check entry');
            self::assertSame('ok', $entry['status']);
        }
    }

    public function testResponseHasIso8601Time(): void
    {
        $pdo = $this->newPdoWithCompleteSchema();
        $controller = new ReadinessController(static fn (): PDO => $pdo);

        $response = $controller->handle(new HttpRequest('GET', '/api/v1/health/readiness'));

        self::assertArrayHasKey('time', $response->body);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/',
            $response->body['time'],
        );
    }

    private function newPdoWithCompleteSchema(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec(
            'CREATE TABLE item ('
            .'id INTEGER PRIMARY KEY, name TEXT, jan_code TEXT, isbn TEXT, '
            .'label_code TEXT, note TEXT, status TEXT, storage_location_id INTEGER'
            .')',
        );
        $pdo->exec('CREATE TABLE category (id INTEGER PRIMARY KEY, name_ja TEXT)');
        $pdo->exec('CREATE TABLE storage_location (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec('CREATE TABLE auth_provider (id INTEGER PRIMARY KEY, type TEXT, enabled INTEGER)');
        $pdo->exec('CREATE TABLE feature_flag (key_name TEXT PRIMARY KEY, enabled INTEGER)');
        $pdo->exec('CREATE TABLE pairing_code (token_hash TEXT PRIMARY KEY, expires_at TEXT, member_id TEXT)');
        $pdo->exec(
            'CREATE TABLE device_token ('
            .'id INTEGER PRIMARY KEY, token_hash TEXT, member_id TEXT, scopes TEXT'
            .')',
        );

        return $pdo;
    }

    /**
     * @param list<array{name: string, status: string, detail?: string}> $checks
     *
     * @return array{name: string, status: string, detail?: string}|null
     */
    private static function firstCheckNamed(array $checks, string $name): ?array
    {
        foreach ($checks as $c) {
            if ($c['name'] === $name) {
                return $c;
            }
        }

        return null;
    }
}
