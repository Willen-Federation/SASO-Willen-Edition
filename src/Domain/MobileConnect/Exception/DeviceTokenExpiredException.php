<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

final class DeviceTokenExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            errorCode: ErrorCode::MobileTokenExpired,
            message: 'Device token has expired.',
        );
    }
}
