<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

/**
 * Per-request context the application hands to {@see AuthProvider::beginLogin()}.
 *
 * Used by OIDC/SAML to build the IdP redirect URL: the `returnTo` becomes
 * the `redirect_uri` (OIDC) or the `RelayState` (SAML), the
 * `csrfStateToken` becomes the `state` parameter, and the `nonce` is
 * the OIDC `nonce` claim. The provider is responsible for storing
 * whatever it needs to verify the callback (typically: nonce + state in
 * `$_SESSION`).
 */
final readonly class LoginContext
{
    public function __construct(
        public string $returnTo,
        public string $csrfStateToken,
        public string $nonce,
    ) {
    }
}
