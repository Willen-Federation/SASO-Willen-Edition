<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\StorageLocation;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\StorageLocation;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;

final class PdoStorageLocationRepositoryTest extends TestCase
{
    private PDO $pdo;
    private PdoStorageLocationRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE storage_location (
                id                 INTEGER PRIMARY KEY,
                parent_id          INTEGER,
                code               TEXT NOT NULL UNIQUE,
                name               TEXT NOT NULL,
                position           INTEGER NOT NULL DEFAULT 0,
                depth              INTEGER NOT NULL,
                location_type      TEXT NOT NULL DEFAULT \'bin\',
                description        TEXT,
                capacity           INTEGER,
                notes              TEXT,
                operational_status TEXT NOT NULL DEFAULT \'available\',
                created_at         TEXT NOT NULL,
                updated_at         TEXT NOT NULL
            )',
        );

        $this->repo = new PdoStorageLocationRepository($this->pdo);
    }

    public function testFindByCodeReturnsNullForUnknown(): void
    {
        self::assertNull($this->repo->findByCode(new LocationCode('NOPE')));
    }

    public function testSaveThenFindByCodeRoundTrips(): void
    {
        $node = $this->makeNode(id: 1, parentId: null, depth: 0, code: 'WH1');
        $this->repo->save($node);

        $reread = $this->repo->findByCode(new LocationCode('WH1'));
        self::assertNotNull($reread);
        self::assertTrue($reread->isRoot());
        self::assertSame('WH1', $reread->code->toString());
    }

    public function testSaveUpdatesExistingRow(): void
    {
        $a = $this->makeNode(id: 1, parentId: null, depth: 0, code: 'WH1', name: 'Warehouse 1');
        $this->repo->save($a);

        $renamed = $a->withName('Warehouse One');
        $this->repo->save($renamed);

        $reread = $this->repo->findById(1);
        self::assertNotNull($reread);
        self::assertSame('Warehouse One', $reread->name);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM storage_location');
        self::assertInstanceOf(\PDOStatement::class, $stmt);
        self::assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testListRootsExcludesChildren(): void
    {
        $this->repo->save($this->makeNode(id: 1, parentId: null, depth: 0, code: 'A'));
        $this->repo->save($this->makeNode(id: 2, parentId: 1, depth: 1, code: 'A-1'));
        $this->repo->save($this->makeNode(id: 3, parentId: null, depth: 0, code: 'B'));

        $roots = $this->repo->listRoots();

        self::assertCount(2, $roots);
        self::assertSame(['A', 'B'], array_map(
            static fn (StorageLocation $l): string => $l->code->toString(),
            $roots,
        ));
    }

    public function testListChildrenOrdersByPositionThenId(): void
    {
        $this->repo->save($this->makeNode(id: 1, parentId: null, depth: 0, code: 'A'));
        $this->repo->save($this->makeNode(id: 2, parentId: 1, depth: 1, code: 'A-3', position: 2));
        $this->repo->save($this->makeNode(id: 3, parentId: 1, depth: 1, code: 'A-1', position: 0));
        $this->repo->save($this->makeNode(id: 4, parentId: 1, depth: 1, code: 'A-2', position: 1));

        $children = $this->repo->listChildrenOf(1);

        self::assertSame(['A-1', 'A-2', 'A-3'], array_map(
            static fn (StorageLocation $l): string => $l->code->toString(),
            $children,
        ));
    }

    public function testUniqueCodeIsEnforcedByTheDb(): void
    {
        $this->repo->save($this->makeNode(id: 1, parentId: null, depth: 0, code: 'A'));

        $this->expectException(\PDOException::class);

        $this->repo->save($this->makeNode(id: 2, parentId: null, depth: 0, code: 'A'));
    }

    public function testDeleteRemovesRow(): void
    {
        $this->repo->save($this->makeNode(id: 1, parentId: null, depth: 0, code: 'A'));
        $this->repo->delete(1);

        self::assertNull($this->repo->findById(1));
    }

    private function makeNode(
        int $id,
        ?int $parentId,
        int $depth,
        string $code,
        string $name = 'Bin',
        int $position = 0,
    ): StorageLocation {
        $now = new DateTimeImmutable('2026-04-27 10:00:00');

        return new StorageLocation(
            id: $id,
            parentId: $parentId,
            code: new LocationCode($code),
            name: $name,
            position: $position,
            depth: $depth,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
