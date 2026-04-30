<?php

declare(strict_types=1);

namespace Saso\Infrastructure\FeatureFlag;

use DateTime;
use OpenFeature\interfaces\common\Metadata as MetadataInterface;
use OpenFeature\interfaces\flags\EvaluationContext;
use OpenFeature\interfaces\provider\Provider;
use OpenFeature\interfaces\provider\ResolutionDetails;
use OpenFeature\interfaces\provider\ResolutionError;
use Psr\Log\LoggerInterface;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

/**
 * OpenFeature provider backed by our PDO repository.
 */
final class DbProvider implements Provider
{
    /**
     * @var array<string, \Saso\Domain\Feature\FeatureFlag|null> Request-scoped cache
     */
    private array $cache = [];

    public function __construct(
        private readonly FeatureFlagRepository $repository,
    ) {
    }

    public function setLogger(LoggerInterface $logger): void
    {
        // Logger accepted but not used — provider operates silently.
    }

    public function getMetadata(): MetadataInterface
    {
        return new class () implements MetadataInterface {
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
            return $this->buildResolution($defaultValue, 'FLAG_NOT_FOUND');
        }

        if (!$flag->enabled) {
            return $this->buildResolution(false, 'DISABLED');
        }

        if ($flag->rolloutPercent < 100) {
            if ($flag->rolloutPercent === 0) {
                return $this->buildResolution(false, 'DISABLED');
            }
            $hash = crc32($flagKey.($context?->getTargetingKey() ?? '')) % 100;
            if ($hash >= $flag->rolloutPercent) {
                return $this->buildResolution(false, 'DISABLED');
            }
        }

        return $this->buildResolution(true, 'TARGETING_MATCH');
    }

    public function resolveStringValue(string $flagKey, string $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH');
    }

    public function resolveIntegerValue(string $flagKey, int $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH');
    }

    public function resolveFloatValue(string $flagKey, float $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH');
    }

    public function resolveObjectValue(string $flagKey, array $defaultValue, ?EvaluationContext $context = null): ResolutionDetails
    {
        return $this->buildResolution($defaultValue, 'TYPE_MISMATCH');
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

    /** @param bool|string|int|float|DateTime|array<mixed>|null $value */
    private function buildResolution(bool|string|int|float|DateTime|array|null $value, string $reason): ResolutionDetails
    {
        return new class ($value, $reason) implements ResolutionDetails {
            /** @param bool|string|int|float|DateTime|array<mixed>|null $value */
            public function __construct(
                private readonly bool|string|int|float|DateTime|array|null $value,
                private readonly string $reason,
            ) {
            }

            public function getValue(): bool|string|int|float|DateTime|array|null
            {
                return $this->value;
            }

            public function getError(): ?ResolutionError
            {
                return null;
            }

            public function getReason(): ?string
            {
                return $this->reason;
            }

            public function getVariant(): ?string
            {
                return null;
            }
        };
    }
}
