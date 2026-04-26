<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown by {@see \Saso\Domain\Auth\AuthProvider} implementations when
 * their stored configuration is incomplete, unreachable, or otherwise
 * unusable for driving a login (or a logout).
 *
 * Operators see this only when something has changed at the IdP after
 * the provider was registered — a rotated client secret, a revoked
 * SAML certificate, an unreachable discovery URL. The provider stays
 * disabled until an admin updates the row in the `auth_provider` table
 * (M4).
 */
final class ProviderMisconfiguredException extends DomainException
{
    public static function for(string $providerName, string $reason): self
    {
        return new self(
            ErrorCode::AuthProviderMisconfigured,
            sprintf('Provider "%s" is misconfigured: %s', $providerName, $reason),
            ['provider' => $providerName, 'reason' => $reason],
        );
    }
}
