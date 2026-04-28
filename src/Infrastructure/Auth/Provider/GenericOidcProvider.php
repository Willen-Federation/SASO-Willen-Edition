<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

/**
 * Pure OpenID Connect provider — works against any IdP that publishes a
 * `.well-known/openid-configuration` discovery document (Keycloak, Okta,
 * Microsoft Entra, generic OIDC stacks). No vendor decoration; everything
 * comes from the discovery doc.
 */
final class GenericOidcProvider extends BaseOidcProvider
{
}
