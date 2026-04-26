<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

/**
 * Drives how a `system_setting` row is parsed and rendered (cf. ADR 0006).
 *
 * Stored verbatim in `system_setting.value_type`; values are stable
 * contracts. `secret` is special-cased: writes encrypt the value with
 * {@see \Saso\Infrastructure\Auth\Crypto\SecretEncryptor} before
 * persisting, reads decrypt on access, and the admin UI renders the
 * cell as `●●●` instead of the plaintext.
 */
enum SettingType: string
{
    case String = 'string';
    case Int    = 'int';
    case Bool   = 'bool';
    case Json   = 'json';
    case Secret = 'secret';

    public function isSecret(): bool
    {
        return $this === self::Secret;
    }
}
