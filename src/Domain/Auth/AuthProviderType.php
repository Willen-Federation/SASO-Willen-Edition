<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

/**
 * Discriminator for {@see AuthProvider} implementations (cf. ADR 0003).
 *
 * Stored verbatim in the `auth_provider.type` column; values are stable
 * contracts. Adding a third protocol later (WebAuthn/passkeys, social
 * sign-in, …) is an append-only operation here.
 */
enum AuthProviderType: string
{
    case Local = 'local';
    case Oidc  = 'oidc';
    case Saml  = 'saml';
}
