<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

/**
 * Per-request context for {@see AuthProvider::beginLogout()}.
 *
 * `idTokenHint` is what OIDC's RP-Initiated Logout requires (the IdP
 * accepts the prior `id_token` so it can match the session to terminate).
 * `returnTo` is where the IdP should redirect the user after they are
 * signed out.
 */
final readonly class LogoutContext
{
    public function __construct(
        public string $returnTo,
        public ?string $idTokenHint = null,
    ) {
    }
}
