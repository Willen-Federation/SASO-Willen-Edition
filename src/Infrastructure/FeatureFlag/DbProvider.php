<?php

declare(strict_types=1);

namespace Saso\Infrastructure\FeatureFlag;

use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\Metadata;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

/**
 * OpenFeature provider backed by our PDO repository.
 */
final class DbProvider implements Provider
{
    private const PROVIDER_NAME = 'SasoDbProvider';

    /**
     * @var array<string, \Saso\Domain\Feature\FeatureFlag|null> Request-scoped cache
     */
    private array $cache = [];

    public function __construct(
        private readonly FeatureFlagRepository $repository,
    ) {
    }

    public function getMetadata(): Metadata
    {
        return new class() implements Metadata {
            public function getName(): string
            {
                return 'SasoDbProvider';
            }
        };
    }

    public function getHooks(): array
    {
        return [];
    }

    public function resolveBooleanValue(string $flagKey, bool $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        $flag = $this->getFlag($flagKey);

        if ($flag === null) {
            return $this->buildResolution($defaultValue, 'FLAG_NOT_FOUND', 'Flag not found in database');
        }

        if (!$flag->enabled) {
            return $this->buildResolution(false, 'DISABLED', 'Flag is disabled');
        }

        // Simplistic rollout check based on targetting or percentages could go here.
        if ($flag->rolloutPercent < 100) {
            // For now, if not 100%, we default to false unless user hashes into it.
            // Simplified for demonstration.
            if ($flag->rolloutPercent === 0) {
                return $this->buildResolution(false, 'DISABLED', 'Rollout is 0%');
            }
            $hash = crc32($flagKey . ($context?->getTargetingKey() ?? '')) % 100;
            if ($hash >= $flag->rolloutPercent) {
                return $this->buildResolution(false, 'DISABLED', 'Excluded by rollout percentage');
            }
        }

        return $this->buildResolution(true, 'TARGETING_MATCH', 'Flag enabled');
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH', 'DB Provider only supports booleans');
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH', 'DB Provider only supports booleans');
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH', 'DB Provider only supports booleans');
    }

    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH', 'DB Provider only supports booleans');
    }

    private function getFlag(string $key): ?\Saso\Domain\Feature\FeatureFlag
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
            $flag = $this->repository->findByKey(new FeatureKey($key));
            $this->cache[$key] = $flag;
            return $flag;
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildResolution(mixed $value, string $reason, string $errorMessage = ''): ResolutionDetails
    {
        // Depending on OpenFeature SDK version, ResolutionDetails might be instantiated directly
        // or through a factory. Assuming a concrete class or an anonymous class.
        return new class($value, $reason, $errorMessage) implements ResolutionDetails {
            public function __construct(
                private mixed $value,
                private string $reason,
                private string $errorMessage
            ) {}
            public function getValue(): mixed { return $this->value; }
            public function getErrorCode(): ?string { return null; }
            public function getReason(): ?string { return $this->reason; }
            public function getVariant(): ?string { return null; }
            public function getFlagMetadata(): array { return []; }
        };
    }
}
