<?php

declare(strict_types=1);

namespace Saso\Domain\Ai\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when the AI provider returns an HTTP 429 / equivalent —
 * either the operator's account-level quota is exhausted or the
 * SASO-side rate limiter (M6-D, Redis-backed) refused the call.
 *
 * The fallback chain (M6-F) catches this and retries against the next
 * configured provider; if none succeed, the response surfaces as
 * `SASO-AI-8002` to the caller.
 */
final class AiRateLimitedException extends DomainException
{
    public static function for(string $providerName, ?int $retryAfterSeconds = null): self
    {
        return new self(
            ErrorCode::AiRateLimited,
            sprintf('AI provider "%s" rate-limited the request.', $providerName),
            ['provider' => $providerName, 'retry_after_seconds' => $retryAfterSeconds],
        );
    }
}
