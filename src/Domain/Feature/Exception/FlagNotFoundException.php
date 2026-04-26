<?php

declare(strict_types=1);

namespace Saso\Domain\Feature\Exception;

use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when call sites ask for a feature flag that is not registered
 * in `feature_flag`. The OpenFeature provider (M4-E2) emits this in
 * strict mode; in lenient mode it logs a warning and returns the
 * caller-supplied default.
 */
final class FlagNotFoundException extends DomainException
{
    public static function for(FeatureKey $key): self
    {
        return new self(
            ErrorCode::FlagNotFound,
            sprintf('No feature_flag row found for key "%s".', $key->toString()),
            ['key' => $key->toString()],
        );
    }
}
