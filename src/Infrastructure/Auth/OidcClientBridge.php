<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth;

use Jumbojett\OpenIDConnectClient;

/**
 * Thin subclass that promotes the protected helpers we need into the public API.
 *
 * jumbojett v1.x keeps `getProviderConfigValue()` and `setNonce()` protected.
 * Rather than calling them via reflection (which violates encapsulation and
 * breaks PHPStan), we extend the class and re-export the two methods we need:
 *   - `getEndpointConfig()` — reads a key from the OIDC discovery document
 *   - `setNonceValue()`    — forwards the caller-chosen nonce to jumbojett
 *
 * No new behaviour is introduced; this is purely an access-level bridge.
 */
final class OidcClientBridge extends OpenIDConnectClient
{
    public function getEndpointConfig(string $param, mixed $default = null): mixed
    {
        return $this->getProviderConfigValue($param, $default);
    }

    public function setNonceValue(string $nonce): string
    {
        return $this->setNonce($nonce);
    }
}
