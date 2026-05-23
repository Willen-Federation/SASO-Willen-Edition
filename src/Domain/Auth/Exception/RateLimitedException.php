<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by the REST `/auth/*` endpoints when the configured per-IP / per-user
 * rate limit on failed attempts is exceeded. Subsequent calls are rejected
 * until the window expires.
 *
 * Maps to {@see ErrorCode::AuthRateLimited} → HTTP 429.
 */
final class RateLimitedException extends DomainException
{
    public function __construct(
        string $message = 'Too many authentication attempts. Try again later.',
        private readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct(
            errorCode: ErrorCode::AuthRateLimited,
            message: $message,
        );
    }

    /**
     * Server-side hint for the `Retry-After` response header. Null when the
     * limiter cannot estimate the wait time.
     */
    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
