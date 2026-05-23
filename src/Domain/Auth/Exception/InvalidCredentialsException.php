<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by {@see \Saso\Application\Auth\VerifyCredentialsService} when the
 * submitted username / password pair does not resolve to an active member.
 *
 * The message is intentionally generic — "no such user" and "wrong password"
 * collapse into the same response so callers cannot enumerate valid usernames
 * by timing or by the wording of the response body (OWASP ASVS V2.2.1).
 *
 * Maps to {@see ErrorCode::AuthInvalidCredentials} → HTTP 401.
 */
final class InvalidCredentialsException extends DomainException
{
    public function __construct(string $message = 'Invalid username or password.')
    {
        parent::__construct(
            errorCode: ErrorCode::AuthInvalidCredentials,
            message: $message,
        );
    }
}
