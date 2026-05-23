<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by `POST /api/v1/auth/password` when the proposed `newPassword`
 * fails the policy. The policy mirrors what
 * {@see \saso\entity\Member::passwordConstraint()} accepts on the legacy
 * flow — 8–64 characters, alphanumeric plus `_`/`-`.
 *
 * Maps to {@see ErrorCode::AuthPasswordPolicyViolation} → HTTP 422.
 */
final class PasswordPolicyViolationException extends DomainException
{
    public function __construct(string $message = 'New password does not meet the password policy.')
    {
        parent::__construct(
            errorCode: ErrorCode::AuthPasswordPolicyViolation,
            message: $message,
        );
    }
}
