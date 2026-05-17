<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by {@see ConnectController} when the request body is missing
 * required fields ("token", "deviceName").
 *
 * Maps to HTTP 400 via {@see ErrorCode::MobileInvalidRequest}.
 */
final class MobileInvalidRequestException extends DomainException
{
    public function __construct(string $message = 'Invalid mobile connect request.')
    {
        parent::__construct(
            errorCode: ErrorCode::MobileInvalidRequest,
            message: $message,
        );
    }
}
