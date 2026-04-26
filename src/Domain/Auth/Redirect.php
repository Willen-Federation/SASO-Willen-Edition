<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

use InvalidArgumentException;

/**
 * Result of {@see AuthProvider::beginLogin()} — a 302/303 redirect to
 * the IdP authorization endpoint (OIDC) or single-sign-on URL (SAML).
 *
 * The Presentation layer decides whether to flush this directly or to
 * embed the URL in a JSON response (e.g. for an SPA). Either way the
 * payload is just a URL — the provider has already attached `state`,
 * `nonce`, and any other transport-specific parameters.
 */
final readonly class Redirect
{
    public function __construct(
        public string $url,
        public int $status = 302,
    ) {
        if ($url === '') {
            throw new InvalidArgumentException('Redirect.url must not be empty.');
        }
        if ($status < 300 || $status >= 400) {
            throw new InvalidArgumentException('Redirect.status must be a 3xx HTTP status.');
        }
    }
}
