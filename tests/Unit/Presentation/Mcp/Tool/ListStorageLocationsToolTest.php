<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;
use Saso\Presentation\Mcp\Tool\ListStorageLocationsTool;

final class ListStorageLocationsToolTest extends TestCase
{
    private PDO $pdo;
    private StorageLocationRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE storage_location (
                id            INTEGER PRIMARY KEY,
                parent_id     INTEGER,
                code          TEXT NOT NULL UNIQUE,
                name          TEXT NOT NULL,
                position      INTEGER NOT NULL DEFAULT 0,
                depth         INTEGER NOT NULL DEFAULT 0,
                location_type TEXT NOT NULL DEFAULT \'bin\',
                description   TEXT,
                capacity      INTEGER,
                notes         TEXT,
                created_at    TEXT NOT NULL,
                updated_at    TEXT NOT NULL
            )',
        );
        $this->repo = new PdoStorageLocationRepository($this->pdo, new DateTimeZone('UTC'));
    }

    public function testNameAndDescription(): void
    {
        $tool = new ListStorageLocationsTool($this->repo);

        self::assertSame('list_storage_locations', $tool->name());
        self::assertNotEmpty($tool->description());
        self::assertNull($tool->requiredScope());
    }

    public function testInputSchemaIsValidShape(): void
    {
        $tool   = new ListStorageLocationsTool($this->repo);
        $schema = $tool->inputSchema();

        self::assertSame('object', $schema['type']);
    }

    public function testInvokeReturnsEmptyWhenNoRoots(): void
    {
        $tool   = new ListStorageLocationsTool($this->repo);
        $result = $tool->invoke([], deviceId: 1);

        self::assertSame([], $result['locations']);
        self::assertSame(0, $result['total']);
    }

    public function testInvokeReturnsRoots(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->repo->save(new StorageLocation(
            id: 1,
            parentId: null,
            code: new LocationCode('WH1'),
            name: 'Warehouse 1',
            position: 0,
            depth: 0,
            createdAt: $now,
            updatedAt: $now,
        ));

        $tool   = new ListStorageLocationsTool($this->repo);
        $result = $tool->invoke([], deviceId: 1);

        self::assertCount(1, $result['locations']);
        self::assertSame('Warehouse 1', $result['locations'][0]['name']);
        self::assertSame('WH1', $result['locations'][0]['code']);
    }

    public function testInvokeWithParentIdListsChildren(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->repo->save(new StorageLocation(1, null, new LocationCode('WH1'), 'WH1', 0, 0, $now, $now));
        $this->repo->save(new StorageLocation(2, 1, new LocationCode('WH1-A'), 'WH1-A', 0, 1, $now, $now));

        $tool   = new ListStorageLocationsTool($this->repo);
        $result = $tool->invoke(['parentId' => 1], deviceId: 1);

        self::assertCount(1, $result['locations']);
        self::assertSame('WH1-A', $result['locations'][0]['name']);
    }
}
