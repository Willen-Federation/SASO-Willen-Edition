<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by the REST `/auth/*` endpoints when the JSON body is missing
 * required fields or contains values of the wrong shape (e.g. non-string
 * password).
 *
 * Maps to {@see ErrorCode::AuthMalformedRequest} → HTTP 422.
 */
final class MalformedRequestException extends DomainException
{
    public function __construct(string $message = 'Authentication request body is malformed.')
    {
        parent::__construct(
            errorCode: ErrorCode::AuthMalformedRequest,
            message: $message,
        );
    }
}
