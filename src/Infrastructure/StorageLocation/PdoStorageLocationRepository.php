<?php

declare(strict_types=1);

namespace Saso\Infrastructure\StorageLocation;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;

/**
 * PDO-backed {@see StorageLocationRepository}.
 *
 * SQL is portable across MariaDB (production) and SQLite (tests).
 * The `code` column carries a UNIQUE index; duplicate inserts raise
 * the underlying `PDOException` which the admin UI translates into a
 * user-facing validation error (`SASO-CONFIG-6xxx` range, M6-E2).
 */
final class PdoStorageLocationRepository implements StorageLocationRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findById(int $id): ?StorageLocation
    {
        $stmt = $this->pdo->prepare('SELECT * FROM storage_location WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByCode(LocationCode $code): ?StorageLocation
    {
        $stmt = $this->pdo->prepare('SELECT * FROM storage_location WHERE code = :code');
        $stmt->execute(['code' => $code->toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listRoots(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM storage_location WHERE parent_id IS NULL ORDER BY position ASC, id ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): StorageLocation => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function listChildrenOf(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM storage_location WHERE parent_id = :pid ORDER BY position ASC, id ASC',
        );
        $stmt->execute(['pid' => $parentId]);

        return array_map(
            fn (array $row): StorageLocation => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function save(StorageLocation $location): StorageLocation
    {
        $now      = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $existing = $this->findById($location->id);

        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO storage_location (id, parent_id, code, name, position, depth, '.
                'created_at, updated_at) VALUES (:id, :parent, :code, :name, :pos, :depth, :ca, :ua)',
            );
            $stmt->bindValue('id', $location->id, PDO::PARAM_INT);
            $stmt->bindValue('parent', $location->parentId, $location->parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('code', $location->code->toString());
            $stmt->bindValue('name', $location->name);
            $stmt->bindValue('pos', $location->position, PDO::PARAM_INT);
            $stmt->bindValue('depth', $location->depth, PDO::PARAM_INT);
            $stmt->bindValue('ca', $location->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE storage_location SET parent_id = :parent, code = :code, name = :name, '.
                'position = :pos, depth = :depth, updated_at = :ua WHERE id = :id',
            );
            $stmt->bindValue('id', $location->id, PDO::PARAM_INT);
            $stmt->bindValue('parent', $location->parentId, $location->parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('code', $location->code->toString());
            $stmt->bindValue('name', $location->name);
            $stmt->bindValue('pos', $location->position, PDO::PARAM_INT);
            $stmt->bindValue('depth', $location->depth, PDO::PARAM_INT);
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        }

        $reread = $this->findById($location->id);
        if ($reread === null) {
            throw new \RuntimeException(sprintf(
                'PdoStorageLocationRepository::save lost row %d after write.',
                $location->id,
            ));
        }

        return $reread;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM storage_location WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): StorageLocation
    {
        $parent = $row['parent_id'] ?? null;

        return new StorageLocation(
            id: (int) $row['id'],
            parentId: $parent === null ? null : (int) $parent,
            code: new LocationCode((string) $row['code']),
            name: (string) $row['name'],
            position: (int) $row['position'],
            depth: (int) $row['depth'],
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }
}
