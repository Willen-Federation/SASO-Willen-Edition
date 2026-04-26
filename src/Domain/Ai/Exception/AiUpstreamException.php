<?php

declare(strict_types=1);

namespace Saso\Domain\Ai\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Throwable;

/**
 * Catch-all for transient AI provider failures (5xx, network timeout,
 * DNS error). The fallback chain (M6-F) treats this exception as
 * eligible for retry against the next configured provider.
 *
 * Maps to `SASO-INFRA-9000` rather than a dedicated `SASO-AI-…` code
 * because at this level the failure looks indistinguishable from any
 * other upstream-network blip.
 */
final class AiUpstreamException extends DomainException
{
    public static function for(string $providerName, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            ErrorCode::InfraUnhandled,
            sprintf('AI provider "%s" is currently unreachable: %s', $providerName, $reason),
            ['provider' => $providerName, 'reason' => $reason],
            $previous,
        );
    }
}
