<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

final class DeviceTokenNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            errorCode: ErrorCode::MobileTokenNotFound,
            message: 'Device token not found.',
        );
    }
}
