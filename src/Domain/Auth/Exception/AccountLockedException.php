<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when a credential check succeeds but the account is administratively
 * disabled (locked by an operator, soft-deleted, or otherwise barred from
 * signing in).
 *
 * The underlying Member table does not yet carry a lock flag, so the live
 * code path never reaches this exception. It is wired so that operators can
 * later enable lockout (e.g. via a `Member.locked` column) without changing
 * the REST contract — clients already see {@see ErrorCode::AuthAccountLocked}
 * declared in the OpenAPI spec.
 *
 * Maps to {@see ErrorCode::AuthAccountLocked} → HTTP 423.
 */
final class AccountLockedException extends DomainException
{
    public function __construct(string $message = 'Account is locked.')
    {
        parent::__construct(
            errorCode: ErrorCode::AuthAccountLocked,
            message: $message,
        );
    }
}
