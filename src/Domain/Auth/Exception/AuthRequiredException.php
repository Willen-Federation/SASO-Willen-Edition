<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Throwable;

/**
 * Thrown by the API layer when a request that requires a Bearer token
 * either omits the `Authorization` header or presents a token the JWT
 * service refuses to verify (malformed, signature mismatch, expired,
 * missing `sub`).
 *
 * Maps to `ErrorCode::AuthUnauthorized` (`SASO-AUTH-1004`, HTTP 401).
 *
 * Why a dedicated class — without it, the bare `RuntimeException` raised
 * by `JwtGuard::authenticate()` and `JwtService::verify()` falls through
 * `ProblemExceptionHandler`'s `DomainException` branch and is reported as
 * `SASO-INFRA-9000` (500). That mismatch made every unauthenticated API
 * call surface as an "internal server error" instead of the 401 the call
 * sites' docstrings promise.
 */
final class AuthRequiredException extends DomainException
{
    /**
     * Header is absent or does not start with `Bearer `.
     */
    public static function missing(): self
    {
        return new self(
            errorCode: ErrorCode::AuthUnauthorized,
            message: 'Missing or malformed Authorization header.',
        );
    }

    /**
     * The Bearer token failed verification (bad signature, expired, malformed
     * payload, etc.). The original `RuntimeException` from `JwtService::verify()`
     * is chained as `$previous` so operators can still trace which specific
     * verification step failed via the log line.
     */
    public static function invalidToken(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            errorCode: ErrorCode::AuthUnauthorized,
            message: $reason,
            previous: $previous,
        );
    }
}
