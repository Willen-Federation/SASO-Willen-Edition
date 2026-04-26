<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

/**
 * Read-only view of the callback request from the IdP.
 *
 * The router builds this from the incoming HTTP request and passes it to
 * {@see AuthProvider::completeLogin()}. The provider extracts whatever
 * it needs (`code` + `state` for OIDC, `SAMLResponse` for SAML, or the
 * form fields for `LocalProvider`).
 */
final readonly class CallbackRequest
{
    /**
     * @param array<string, string> $query parsed query string
     * @param array<string, string> $body parsed form-encoded body or POST array
     * @param array<string, string> $headers request headers (lower-case keys)
     */
    public function __construct(
        public string $method,
        public string $uri,
        public array $query = [],
        public array $body = [],
        public array $headers = [],
    ) {
    }
}
