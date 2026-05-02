<?php

declare(strict_types=1);

namespace Saso\Domain\StorageLocation\Repository;

use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\StorageLocation;

/**
 * Read/write contract for `storage_location` rows (cf. ADR 0011).
 *
 * `findByCode()` is the hot path — barcode scanners hit it on every
 * scan. `listChildrenOf()` powers the admin-UI tree drilldown;
 * `listRoots()` is its top-level entry point.
 */
interface StorageLocationRepository
{
    public function findById(int $id): ?StorageLocation;

    public function findByCode(LocationCode $code): ?StorageLocation;

    /**
     * @return list<StorageLocation>
     */
    public function listRoots(): array;

    /**
     * @return list<StorageLocation>
     */
    public function listChildrenOf(int $parentId): array;

    public function save(StorageLocation $location): StorageLocation;

    /**
     * @return list<StorageLocation>
     */
    public function listPinned(): array;

    public function delete(int $id): void;
}
