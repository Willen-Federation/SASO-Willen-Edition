<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Auth;

use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Saso\Application\Auth\AdminGuard;
use Stringable;

/**
 * AdminGuard intentionally tolerates partially-migrated schemas: the
 * `Member.role` column and the `Role` table are M4 additions. These tests
 * exercise both happy-path lookups and the migration-tolerant fallbacks,
 * and verify that the silent fallbacks now emit a structured log line so
 * an actual schema corruption is not invisible.
 */
final class AdminGuardTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testIsAdminReturnsFalseForNullOrEmptyMemberId(): void
    {
        $guard = new AdminGuard($this->pdo);
        self::assertFalse($guard->isAdmin(null));
        self::assertFalse($guard->isAdmin(''));
    }

    public function testIsAdminReadsRoleColumnWhenAvailable(): void
    {
        $this->createMemberTableWithRole();
        $this->pdo->exec("INSERT INTO Member (id, role) VALUES ('alice', 'admin')");
        $this->pdo->exec("INSERT INTO Member (id, role) VALUES ('bob', 'operator')");

        $guard = new AdminGuard($this->pdo);

        self::assertTrue($guard->isAdmin('alice'));
        self::assertFalse($guard->isAdmin('bob'));
    }

    public function testIsAdminFallsBackToBootstrapWhenRoleColumnMissing(): void
    {
        $this->createMemberTableWithoutRole();
        $this->pdo->exec("INSERT INTO Member (id) VALUES ('bootstrap')");
        $this->pdo->exec("INSERT INTO Member (id) VALUES ('alice')");

        $logger = new TestLogger();
        $guard  = new AdminGuard($this->pdo, $logger);

        self::assertTrue($guard->isAdmin('bootstrap'));
        self::assertFalse($guard->isAdmin('alice'));
        // Each silent fallback should emit one structured warning so an
        // actual schema corruption isn't invisible.
        self::assertCount(2, $logger->records);
        self::assertSame(['warning', 'warning'], $logger->levelsRecorded());
        self::assertStringContainsString(
            'Member.role lookup failed',
            $logger->records[0]['message'],
        );
        self::assertSame('bootstrap', $logger->records[0]['context']['memberId']);
        self::assertSame('alice', $logger->records[1]['context']['memberId']);
    }

    public function testHasPermissionReturnsTrueWhenPermissionPresent(): void
    {
        $this->createMemberAndRoleTables();
        $this->pdo->exec("INSERT INTO Member (id, role) VALUES ('alice', 'manager')");
        $this->pdo->exec("INSERT INTO Role (name, permissions) VALUES ('manager', '[\"item\",\"verify\"]')");

        $guard = new AdminGuard($this->pdo);

        self::assertTrue($guard->hasPermission('alice', 'item'));
        self::assertTrue($guard->hasPermission('alice', 'verify'));
        self::assertFalse($guard->hasPermission('alice', 'admin'));
    }

    public function testHasPermissionFallsBackToAdminCheckWhenRoleTableMissing(): void
    {
        $this->createMemberTableWithoutRole();
        $this->pdo->exec("INSERT INTO Member (id) VALUES ('bootstrap')");

        $logger = new TestLogger();
        $guard  = new AdminGuard($this->pdo, $logger);

        // No Role table → fallback to isAdmin(bootstrap) === true.
        self::assertTrue($guard->hasPermission('bootstrap', 'whatever'));
        self::assertNotEmpty($logger->records, 'Fallback must be logged.');
    }

    public function testGetPermissionsReturnsListFromRoleRow(): void
    {
        $this->createMemberAndRoleTables();
        $this->pdo->exec("INSERT INTO Member (id, role) VALUES ('alice', 'manager')");
        $this->pdo->exec("INSERT INTO Role (name, permissions) VALUES ('manager', '[\"item\",\"verify\"]')");

        $guard = new AdminGuard($this->pdo);

        self::assertSame(['item', 'verify'], $guard->getPermissions('alice'));
    }

    public function testGetPermissionsReturnsEmptyForNullOrEmpty(): void
    {
        $guard = new AdminGuard($this->pdo);
        self::assertSame([], $guard->getPermissions(null));
        self::assertSame([], $guard->getPermissions(''));
    }

    public function testGetPermissionsFiltersOutNonStringEntries(): void
    {
        // A misconfigured Role row could store a heterogeneous JSON array.
        // The return type is list<string>, so non-string entries must be
        // filtered out before reaching callers.
        $this->createMemberAndRoleTables();
        $this->pdo->exec("INSERT INTO Member (id, role) VALUES ('alice', 'manager')");
        $this->pdo->exec("INSERT INTO Role (name, permissions) VALUES ('manager', '[\"item\", 42, true, \"verify\"]')");

        $guard = new AdminGuard($this->pdo);

        self::assertSame(['item', 'verify'], $guard->getPermissions('alice'));
    }

    public function testGetPermissionsBootstrapFallbackReturnsAllPermissionsWhenSchemaMissing(): void
    {
        $this->createMemberTableWithoutRole();
        $this->pdo->exec("INSERT INTO Member (id) VALUES ('bootstrap')");

        $logger = new TestLogger();
        $guard  = new AdminGuard($this->pdo, $logger);

        $perms = $guard->getPermissions('bootstrap');
        // All keys of Role::PERMISSIONS — non-empty and string-only.
        self::assertNotEmpty($perms);
        foreach ($perms as $key) {
            self::assertIsString($key);
        }
        self::assertNotEmpty($logger->records, 'Schema fallback must be logged.');
    }

    public function testGetPermissionsReturnsEmptyWhenRolePermissionsIsNotArray(): void
    {
        $this->createMemberAndRoleTables();
        $this->pdo->exec("INSERT INTO Member (id, role) VALUES ('alice', 'manager')");
        // Stored as JSON string of a scalar — json_decode returns a scalar.
        $this->pdo->exec("INSERT INTO Role (name, permissions) VALUES ('manager', '\"corrupted\"')");

        $guard = new AdminGuard($this->pdo);

        self::assertSame([], $guard->getPermissions('alice'));
    }

    public function testDefaultLoggerIsNullLoggerSoSilentCallSitesKeepCompiling(): void
    {
        // Source compatibility: existing call sites use new AdminGuard($pdo)
        // and pass no logger. The default must be a no-op so they keep
        // working without code changes.
        $guard = new AdminGuard($this->pdo);
        self::assertFalse($guard->isAdmin(null));
        // No exception, no required logger argument.
        self::assertTrue(true);
    }

    private function createMemberTableWithRole(): void
    {
        $this->pdo->exec('CREATE TABLE Member (id VARCHAR(20) PRIMARY KEY, role VARCHAR(20) DEFAULT "operator")');
    }

    private function createMemberTableWithoutRole(): void
    {
        $this->pdo->exec('CREATE TABLE Member (id VARCHAR(20) PRIMARY KEY)');
    }

    private function createMemberAndRoleTables(): void
    {
        $this->createMemberTableWithRole();
        $this->pdo->exec('CREATE TABLE Role (name VARCHAR(20) PRIMARY KEY, permissions TEXT)');
    }
}

/**
 * Minimal PSR-3 logger that captures records for assertions. Avoids a hard
 * dependency on monolog/test-handler so the unit suite only needs psr/log.
 */
final class TestLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /** @return list<string> */
    public function levelsRecorded(): array
    {
        return array_values(array_map(static fn (array $r): string => $r['level'], $this->records));
    }
}
