<?php

declare(strict_types=1);

namespace Saso\Domain\Shared;

/**
 * Canonical SASO error codes — the catalogue referenced by ADR 0004.
 *
 * Each case is a stable identifier of the form `SASO-<DOMAIN>-<NNNN>`. Once
 * a code is added it is never renamed or reassigned; deprecated codes stay
 * in the enum (and in `docs/error-codes.md`) so logs from older releases
 * remain decodable.
 *
 * Adding a code is a three-place edit: this enum, `docs/error-codes.md`,
 * and the translation YAMLs (added in M3-C). The thrown call site lives
 * with the feature that produces it.
 */
enum ErrorCode: string
{
    // ── Authentication & session (1xxx) ──────────────────────────────────
    case AuthInvalidCredentials       = 'SASO-AUTH-1001';
    case AuthSessionExpired           = 'SASO-AUTH-1002';
    case AuthCsrfMismatch             = 'SASO-AUTH-1003';
    case AuthUnauthorized             = 'SASO-AUTH-1004';
    case AuthForbidden                = 'SASO-AUTH-1005';
    case AuthProviderMisconfigured    = 'SASO-AUTH-1006';
    case AuthCallbackStateMismatch    = 'SASO-AUTH-1007';
    case AuthCallbackValidationFailed = 'SASO-AUTH-1008';

    // ── Mobile / QR connect (2xxx) ───────────────────────────────────────
    case MobilePairingCodeNotFound   = 'SASO-MOBILE-2001';
    case MobilePairingCodeExpired    = 'SASO-MOBILE-2002';
    case MobilePairingCodeUsed       = 'SASO-MOBILE-2003';
    case MobileTokenNotFound         = 'SASO-MOBILE-2004';
    case MobileTokenRevoked          = 'SASO-MOBILE-2005';
    case MobileTokenExpired          = 'SASO-MOBILE-2006';
    case MobileInvalidRequest        = 'SASO-MOBILE-2007';
    case MobileScopeInsufficient     = 'SASO-MOBILE-2008';

    // ── Item domain (3xxx) ───────────────────────────────────────────────
    case ItemNotFound       = 'SASO-ITEM-3001';
    case ItemInvalidStatus  = 'SASO-ITEM-3002';

    // ── Configuration (6xxx) ─────────────────────────────────────────────
    case ConfigSettingNotFound = 'SASO-CONFIG-6001';

    // ── Feature flag (7xxx) ──────────────────────────────────────────────
    case FlagNotFound = 'SASO-FLAG-7001';

    // ── AI gateway (8xxx) — cf. ADR 0009 ─────────────────────────────────
    case AiProviderNotConfigured = 'SASO-AI-8001';
    case AiRateLimited           = 'SASO-AI-8002';
    case AiResponseMalformed     = 'SASO-AI-8003';
    case AiContextExceeded       = 'SASO-AI-8004';
    case AiContentPolicy         = 'SASO-AI-8005';

    // ── MCP server (Axxx) — cf. ADR 0014 ─────────────────────────────────
    case McpUnknownTool       = 'SASO-MCP-A001';
    case McpMalformedRequest  = 'SASO-MCP-A002';
    case McpScopeInsufficient = 'SASO-MCP-A003';

    // ── Plugin system (Bxxx) — cf. ADR 0015 ──────────────────────────────
    case PluginRegistryCollision = 'SASO-PLUGIN-B001';

    // ── Infrastructure (9xxx) ────────────────────────────────────────────
    case InfraUnhandled           = 'SASO-INFRA-9000';
    case InfraDatabaseUnavailable = 'SASO-INFRA-9001';
    case InfraStorageUnavailable  = 'SASO-INFRA-9002';
    case InfraRouteNotFound       = 'SASO-INFRA-9003';
    case InfraMethodNotAllowed    = 'SASO-INFRA-9004';

    /**
     * HTTP status the response carries when this error is raised.
     *
     * Status is intrinsic to the error's meaning, not the call site — a 401
     * is a 401 whether it is thrown from the login form or from an API
     * controller.
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::AuthInvalidCredentials,
            self::AuthSessionExpired,
            self::AuthUnauthorized        => 401,

            self::AuthCsrfMismatch,
            self::AuthForbidden           => 403,

            self::AuthCallbackStateMismatch,
            self::AuthCallbackValidationFailed => 400,

            self::AuthProviderMisconfigured    => 503,

            self::MobilePairingCodeNotFound,
            self::MobileTokenNotFound          => 404,

            self::MobilePairingCodeExpired,
            self::MobilePairingCodeUsed,
            self::MobileTokenRevoked,
            self::MobileTokenExpired,
            self::MobileInvalidRequest         => 400,

            self::MobileScopeInsufficient      => 403,

            self::ItemNotFound                 => 404,
            self::ItemInvalidStatus            => 422,

            self::ConfigSettingNotFound,
            self::FlagNotFound                 => 404,

            self::AiResponseMalformed,
            self::AiContextExceeded,
            self::AiContentPolicy              => 422,

            self::McpUnknownTool               => 404,
            self::McpMalformedRequest          => 400,
            self::McpScopeInsufficient         => 403,

            self::PluginRegistryCollision      => 409,

            self::AiRateLimited                => 429,

            self::AiProviderNotConfigured      => 503,

            self::InfraRouteNotFound      => 404,
            self::InfraMethodNotAllowed   => 405,

            self::InfraUnhandled          => 500,

            self::InfraDatabaseUnavailable,
            self::InfraStorageUnavailable => 503,
        };
    }

    public function domain(): ErrorDomain
    {
        // Format guaranteed by the value contract: SASO-<DOMAIN>-<NNNN>.
        $segment = explode('-', $this->value)[1];

        return ErrorDomain::from($segment);
    }

    /**
     * Translation key used by the i18n layer (M3-C) to resolve `title` and
     * `detail` strings: `error.SASO-AUTH-1001.title` / `.detail`.
     */
    public function translationKey(): string
    {
        return 'error.'.$this->value;
    }

    /**
     * English fallback title used until M3-C wires the translator. The
     * translator overrides this at render time when a translation exists.
     */
    public function defaultTitle(): string
    {
        return match ($this) {
            self::AuthInvalidCredentials       => 'Invalid credentials',
            self::AuthSessionExpired           => 'Session expired',
            self::AuthCsrfMismatch             => 'CSRF token mismatch',
            self::AuthUnauthorized             => 'Authentication required',
            self::AuthForbidden                => 'Access denied',
            self::AuthProviderMisconfigured    => 'Authentication provider is misconfigured',
            self::AuthCallbackStateMismatch    => 'Authentication callback could not be matched to a pending request',
            self::AuthCallbackValidationFailed => 'Authentication callback failed verification',
            self::MobilePairingCodeNotFound    => 'Pairing code not found',
            self::MobilePairingCodeExpired     => 'Pairing code has expired',
            self::MobilePairingCodeUsed        => 'Pairing code has already been used',
            self::MobileTokenNotFound          => 'Device token not found',
            self::MobileTokenRevoked           => 'Device token has been revoked',
            self::MobileTokenExpired           => 'Device token has expired',
            self::MobileInvalidRequest         => 'Invalid mobile connect request',
            self::MobileScopeInsufficient      => 'Scope insufficient for the requested endpoint',
            self::ItemNotFound                 => 'Item not found',
            self::ItemInvalidStatus            => 'Item status value is not one of the permitted values',
            self::ConfigSettingNotFound        => 'System setting not found',
            self::FlagNotFound                 => 'Feature flag not found',
            self::AiProviderNotConfigured      => 'AI provider is not configured',
            self::AiRateLimited                => 'AI provider rate-limited the request',
            self::AiResponseMalformed          => 'AI provider returned a malformed response',
            self::AiContextExceeded            => 'AI prompt exceeds the provider context window',
            self::AiContentPolicy              => 'AI provider refused the request as policy-violating',
            self::McpUnknownTool               => 'Unknown MCP tool',
            self::McpMalformedRequest          => 'MCP request malformed',
            self::McpScopeInsufficient         => 'Scope insufficient for the requested MCP tool',
            self::PluginRegistryCollision      => 'Plugin attempted to register against a reserved core name',
            self::InfraUnhandled           => 'Internal server error',
            self::InfraDatabaseUnavailable => 'Database unavailable',
            self::InfraStorageUnavailable  => 'Storage unavailable',
            self::InfraRouteNotFound       => 'Endpoint not found',
            self::InfraMethodNotAllowed    => 'Method not allowed',
        };
    }
}
