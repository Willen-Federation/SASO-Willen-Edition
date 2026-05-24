<?php

declare(strict_types=1);

namespace Saso\Domain\Feature\Repository;

use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;

/**
 * Read/write contract for `feature_flag` rows (cf. ADR 0005).
 */
interface FeatureFlagRepository
{
    public function findByKey(FeatureKey $key): ?FeatureFlag;

    public function findById(int $id): ?FeatureFlag;

    /**
     * @return list<FeatureFlag>
     */
    public function listAll(): array;

    /**
     * Returns the next free integer id without pulling rows into PHP.
     *
     * Callers use this when creating a new {@see FeatureFlag} so the id
     * computation does not require `listAll()`. Implementations should
     * delegate to the database (`SELECT MAX(id) + 1 …`) rather than
     * counting in userland.
     */
    public function nextId(): int;

    public function save(FeatureFlag $flag): FeatureFlag;

    public function delete(int $id): void;
}
