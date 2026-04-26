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
    case AuthInvalidCredentials = 'SASO-AUTH-1001';
    case AuthSessionExpired     = 'SASO-AUTH-1002';
    case AuthCsrfMismatch       = 'SASO-AUTH-1003';
    case AuthUnauthorized       = 'SASO-AUTH-1004';
    case AuthForbidden          = 'SASO-AUTH-1005';

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
            self::AuthInvalidCredentials   => 'Invalid credentials',
            self::AuthSessionExpired       => 'Session expired',
            self::AuthCsrfMismatch         => 'CSRF token mismatch',
            self::AuthUnauthorized         => 'Authentication required',
            self::AuthForbidden            => 'Access denied',
            self::InfraUnhandled           => 'Internal server error',
            self::InfraDatabaseUnavailable => 'Database unavailable',
            self::InfraStorageUnavailable  => 'Storage unavailable',
            self::InfraRouteNotFound       => 'Endpoint not found',
            self::InfraMethodNotAllowed    => 'Method not allowed',
        };
    }
}
