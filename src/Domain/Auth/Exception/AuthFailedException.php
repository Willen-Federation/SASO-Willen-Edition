<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by {@see \Saso\Domain\Auth\AuthProvider::completeLogin()} when
 * any verification step fails — invalid credentials, mismatched state /
 * nonce, signature failure, expired authentication context.
 *
 * The exception carries an {@see ErrorCode} drawn from the `AUTH 1xxx`
 * range. Callers do not need to distinguish "wrong password" from
 * "tampered SAML response" at the HTTP boundary — both yield the
 * provider-specific 4xx that the catalogue assigns.
 */
final class AuthFailedException extends DomainException
{
    /**
     * @param array<string, mixed> $context
     */
    public static function with(ErrorCode $code, string $reason = '', array $context = []): self
    {
        return new self($code, $reason, $context);
    }

    public static function invalidCredentials(string $reason = ''): self
    {
        return new self(ErrorCode::AuthInvalidCredentials, $reason);
    }

    public static function stateMismatch(string $reason = 'State token did not match the stored value.'): self
    {
        return new self(ErrorCode::AuthCallbackStateMismatch, $reason);
    }

    public static function callbackInvalid(string $reason): self
    {
        return new self(ErrorCode::AuthCallbackValidationFailed, $reason);
    }
}
