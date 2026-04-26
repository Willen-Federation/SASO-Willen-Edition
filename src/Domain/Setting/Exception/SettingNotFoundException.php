<?php

declare(strict_types=1);

namespace Saso\Domain\Setting\Exception;

use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by {@see \Saso\Domain\Setting\SystemSettingService::require()}
 * when no row exists for the requested key.
 */
final class SettingNotFoundException extends DomainException
{
    public static function for(SettingKey $key): self
    {
        return new self(
            ErrorCode::ConfigSettingNotFound,
            sprintf('No system_setting row found for key "%s".', $key->toString()),
            ['key' => $key->toString()],
        );
    }
}
