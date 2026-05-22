<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

use RuntimeException;
use Throwable;

/**
 * Raised when the Auth0 Management API call cannot be completed.
 *
 * Carries the upstream HTTP status (`0` when the failure happened before
 * the request reached Auth0, e.g. token mint failed or DNS resolution
 * failed) so the My Page UI can distinguish "transient — try again" from
 * "Auth0 says this user has no such passkey".
 */
final class Auth0ManagementApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $upstreamStatus = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
