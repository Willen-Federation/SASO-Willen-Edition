<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

/**
 * In-memory FeatureFlagRepository used across controller tests.
 *
 * Avoids spinning up SQLite for every test that only needs to assert the
 * controller mapped the request shape correctly. Mutations are observable
 * via the public state properties so tests can verify what the controller
 * persisted without relying on round-tripping through find* methods.
 *
 * @internal
 */
final class InMemoryFlagRepo implements FeatureFlagRepository
{
    /** @var array<int, FeatureFlag> */
    public array $byId = [];

    /** @var list<FeatureFlag> */
    public array $saved = [];

    /** @var list<int> */
    public array $deleted = [];

    /** @param list<FeatureFlag> $initial */
    public function __construct(array $initial = [])
    {
        foreach ($initial as $flag) {
            $this->byId[$flag->id] = $flag;
        }
    }

    public function findByKey(FeatureKey $key): ?FeatureFlag
    {
        foreach ($this->byId as $flag) {
            if ($flag->key->equals($key)) {
                return $flag;
            }
        }
        return null;
    }

    public function findById(int $id): ?FeatureFlag
    {
        return $this->byId[$id] ?? null;
    }

    /** @return list<FeatureFlag> */
    public function listAll(): array
    {
        return array_values($this->byId);
    }

    public function nextId(): int
    {
        if ($this->byId === []) {
            return 1;
        }
        return max(array_keys($this->byId)) + 1;
    }

    public function save(FeatureFlag $flag): FeatureFlag
    {
        $this->byId[$flag->id] = $flag;
        $this->saved[]         = $flag;
        return $flag;
    }

    public function delete(int $id): void
    {
        unset($this->byId[$id]);
        $this->deleted[] = $id;
    }
}
