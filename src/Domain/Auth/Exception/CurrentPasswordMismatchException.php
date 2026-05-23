<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by `POST /api/v1/auth/password` when the authenticated user submits
 * the wrong `currentPassword`. Differs from
 * {@see InvalidCredentialsException} so the password-change endpoint can
 * surface an actionable 401 (the session itself stays valid).
 *
 * Maps to {@see ErrorCode::AuthCurrentPasswordMismatch} → HTTP 401.
 */
final class CurrentPasswordMismatchException extends DomainException
{
    public function __construct(string $message = 'Current password did not match.')
    {
        parent::__construct(
            errorCode: ErrorCode::AuthCurrentPasswordMismatch,
            message: $message,
        );
    }
}
