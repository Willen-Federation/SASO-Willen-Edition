<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Plugin;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\PluginRecord;
use Saso\Infrastructure\Plugin\PdoPluginRepository;

final class PdoPluginRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoPluginRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE plugin_registry (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                package         TEXT NOT NULL UNIQUE,
                class           TEXT NOT NULL,
                name            TEXT NOT NULL,
                version         TEXT NOT NULL,
                activated_at    TEXT NOT NULL,
                deactivated_at  TEXT,
                last_seen_at    TEXT,
                settings_json   TEXT
            )',
        );

        $this->repo = new PdoPluginRepository($this->pdo);
    }

    public function testFindByPackageReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findByPackage('vendor/missing'));
    }

    public function testActivateInsertsThenFindByPackageRoundTrips(): void
    {
        $record = $this->makeRecord(package: 'a/b', class: 'Acme\\B\\Plugin');
        $saved  = $this->repo->activate($record);

        self::assertSame('a/b', $saved->package);
        self::assertSame('Acme\\B\\Plugin', $saved->class);

        $reread = $this->repo->findByPackage('a/b');
        self::assertNotNull($reread);
        self::assertTrue($reread->isActive());
    }

    public function testActivateOnExistingPackageRefreshesClassAndVersion(): void
    {
        $first  = $this->makeRecord(package: 'a/b', class: 'Acme\\Old\\Plugin', version: '1.0.0');
        $saved  = $this->repo->activate($first);
        $oldId  = $saved->id;

        // Re-activation with new class + version.
        $second = $this->makeRecord(
            package: 'a/b',
            class: 'Acme\\New\\Plugin',
            version: '2.0.0',
        );
        $reactivated = $this->repo->activate($second);

        self::assertSame($oldId, $reactivated->id, 'id must be preserved across re-activation');
        self::assertSame('Acme\\New\\Plugin', $reactivated->class);
        self::assertSame('2.0.0', $reactivated->version);

        // Still exactly one row.
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM plugin_registry');
        self::assertInstanceOf(\PDOStatement::class, $stmt);
        self::assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testActivateClearsDeactivatedAtOnReactivation(): void
    {
        $r = $this->repo->activate($this->makeRecord(package: 'a/b'));
        $this->repo->deactivate($r->id);

        $deactivated = $this->repo->findById($r->id);
        self::assertNotNull($deactivated);
        self::assertFalse($deactivated->isActive());

        // Re-activate via a fresh record.
        $reactivated = $this->repo->activate($this->makeRecord(package: 'a/b'));
        self::assertTrue($reactivated->isActive());
    }

    public function testListActiveExcludesDeactivated(): void
    {
        $a = $this->repo->activate($this->makeRecord(package: 'a/active', name: 'Active'));
        $b = $this->repo->activate($this->makeRecord(package: 'b/disabled', name: 'Disabled'));
        $this->repo->deactivate($b->id);

        $active = $this->repo->listActive();

        self::assertCount(1, $active);
        self::assertSame($a->id, $active[0]->id);
    }

    public function testListAllOrdersByName(): void
    {
        $this->repo->activate($this->makeRecord(package: 'z/zebra', name: 'Zebra'));
        $this->repo->activate($this->makeRecord(package: 'a/alpha', name: 'Alpha'));

        $names = array_map(
            static fn (PluginRecord $r): string => $r->name,
            $this->repo->listAll(),
        );

        self::assertSame(['Alpha', 'Zebra'], $names);
    }

    public function testMarkSeenUpdatesLastSeenAt(): void
    {
        $r = $this->repo->activate($this->makeRecord(package: 'a/b'));
        self::assertNull($r->lastSeenAt);

        $this->repo->markSeen($r->id);

        $reread = $this->repo->findById($r->id);
        self::assertNotNull($reread);
        self::assertNotNull($reread->lastSeenAt);
    }

    public function testDeactivateSetsDeactivatedAt(): void
    {
        $r = $this->repo->activate($this->makeRecord(package: 'a/b'));
        $this->repo->deactivate($r->id, reason: 'compatibility');

        $reread = $this->repo->findById($r->id);
        self::assertNotNull($reread);
        self::assertFalse($reread->isActive());
        self::assertNotNull($reread->deactivatedAt);
    }

    public function testSettingsJsonRoundTrips(): void
    {
        $settings = ['log_level' => 'warning', 'max_retries' => 3];
        $r        = $this->repo->activate(
            $this->makeRecord(package: 'a/b', settings: $settings),
        );

        $reread = $this->repo->findByPackage('a/b');
        self::assertNotNull($reread);
        self::assertSame($settings, $reread->settings);
        self::assertSame($r->id, $reread->id);
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    private function makeRecord(
        string $package,
        string $class = 'Acme\\Plugin',
        string $name = 'Plugin',
        string $version = '1.0.0',
        ?array $settings = null,
    ): PluginRecord {
        $now = new DateTimeImmutable('2026-04-27 10:00:00');

        // The "id = 1" passed here is overwritten by the DB on insert;
        // it's only required because the PluginRecord constructor
        // enforces id >= 1 (a positive value). The repository reads
        // back the actual auto-increment id.
        return new PluginRecord(
            id: 1,
            package: $package,
            class: $class,
            name: $name,
            version: $version,
            activatedAt: $now,
            deactivatedAt: null,
            lastSeenAt: null,
            settings: $settings,
        );
    }
}
