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

    public function save(FeatureFlag $flag): FeatureFlag;

    public function delete(int $id): void;
}
