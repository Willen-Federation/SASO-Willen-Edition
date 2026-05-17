<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when a device token's `scp` claim does not include the scope a
 * V1 API endpoint declares it needs.
 *
 * The handler maps this to HTTP 403 (cf. RFC 6749 §3.3 — scopes are
 * normative). The required scope travels in the `context` so operators
 * can correlate a refusal with the token issuance.
 */
final class ScopeInsufficientException extends DomainException
{
    public function __construct(string $requiredScope)
    {
        parent::__construct(
            errorCode: ErrorCode::MobileScopeInsufficient,
            message: sprintf('This endpoint requires the "%s" scope.', $requiredScope),
            context: ['requiredScope' => $requiredScope],
        );
    }
}
