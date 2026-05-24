<?php

declare(strict_types=1);

namespace Saso\Infrastructure\StorageLocation;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\LocationType;
use Saso\Domain\StorageLocation\Repository\StorageLocationRepository;
use Saso\Domain\StorageLocation\StorageLocation;
use Saso\Domain\StorageLocation\StorageOperationalStatus;

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

    /** @return list<StorageLocation> */
    public function listPinned(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM storage_location ORDER BY position ASC, id ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): StorageLocation => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
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
                'INSERT INTO storage_location (id, parent_id, code, area_code, name, position, depth, '.
                'location_type, description, capacity, notes, operational_status, '.
                'map_image_id, map_x_ratio, map_y_ratio, created_at, updated_at) '.
                'VALUES (:id, :parent, :code, :area, :name, :pos, :depth, :ltype, :desc, :cap, :notes, :ostatus, '.
                ':mimg, :mx, :my, :ca, :ua)',
            );
            $stmt->bindValue('id', $location->id, PDO::PARAM_INT);
            $stmt->bindValue('parent', $location->parentId, $location->parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('code', $location->code->toString());
            $stmt->bindValue('area', $location->areaCode, $location->areaCode === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('name', $location->name);
            $stmt->bindValue('pos', $location->position, PDO::PARAM_INT);
            $stmt->bindValue('depth', $location->depth, PDO::PARAM_INT);
            $stmt->bindValue('ltype', $location->locationType->value);
            $stmt->bindValue('desc', $location->description);
            $stmt->bindValue('cap', $location->capacity, $location->capacity === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('notes', $location->notes);
            $stmt->bindValue('ostatus', $location->operationalStatus->value);
            $stmt->bindValue('mimg', $location->mapImageId, $location->mapImageId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('mx', $location->mapXRatio === null ? null : (string) $location->mapXRatio, $location->mapXRatio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('my', $location->mapYRatio === null ? null : (string) $location->mapYRatio, $location->mapYRatio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('ca', $location->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE storage_location SET parent_id = :parent, code = :code, area_code = :area, name = :name, '.
                'position = :pos, depth = :depth, location_type = :ltype, description = :desc, '.
                'capacity = :cap, notes = :notes, operational_status = :ostatus, '.
                'map_image_id = :mimg, map_x_ratio = :mx, map_y_ratio = :my, updated_at = :ua WHERE id = :id',
            );
            $stmt->bindValue('id', $location->id, PDO::PARAM_INT);
            $stmt->bindValue('parent', $location->parentId, $location->parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('code', $location->code->toString());
            $stmt->bindValue('area', $location->areaCode, $location->areaCode === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('name', $location->name);
            $stmt->bindValue('pos', $location->position, PDO::PARAM_INT);
            $stmt->bindValue('depth', $location->depth, PDO::PARAM_INT);
            $stmt->bindValue('ltype', $location->locationType->value);
            $stmt->bindValue('desc', $location->description);
            $stmt->bindValue('cap', $location->capacity, $location->capacity === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('notes', $location->notes);
            $stmt->bindValue('ostatus', $location->operationalStatus->value);
            $stmt->bindValue('mimg', $location->mapImageId, $location->mapImageId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('mx', $location->mapXRatio === null ? null : (string) $location->mapXRatio, $location->mapXRatio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue('my', $location->mapYRatio === null ? null : (string) $location->mapYRatio, $location->mapYRatio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
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

        $locationType = isset($row['location_type']) && is_string($row['location_type'])
            ? (LocationType::tryFrom($row['location_type']) ?? LocationType::Bin)
            : LocationType::Bin;

        $capacity = isset($row['capacity']) && $row['capacity'] !== null
            ? (int) $row['capacity']
            : null;

        $operationalStatus = isset($row['operational_status']) && is_string($row['operational_status'])
            ? (StorageOperationalStatus::tryFrom($row['operational_status']) ?? StorageOperationalStatus::Available)
            : StorageOperationalStatus::Available;

        return new StorageLocation(
            id: (int) $row['id'],
            parentId: $parent === null ? null : (int) $parent,
            code: new LocationCode((string) $row['code']),
            name: (string) $row['name'],
            position: (int) $row['position'],
            depth: (int) $row['depth'],
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
            locationType: $locationType,
            description: isset($row['description']) && is_string($row['description']) ? $row['description'] : null,
            capacity: $capacity,
            notes: isset($row['notes']) && is_string($row['notes']) ? $row['notes'] : null,
            operationalStatus: $operationalStatus,
            areaCode: isset($row['area_code']) && is_string($row['area_code']) ? $row['area_code'] : null,
            mapImageId: isset($row['map_image_id']) && $row['map_image_id'] !== null ? (int) $row['map_image_id'] : null,
            mapXRatio: isset($row['map_x_ratio']) && $row['map_x_ratio'] !== null ? (float) $row['map_x_ratio'] : null,
            mapYRatio: isset($row['map_y_ratio']) && $row['map_y_ratio'] !== null ? (float) $row['map_y_ratio'] : null,
        );
    }
}
