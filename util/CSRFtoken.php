<?php
namespace saso\util;

/**
 * Session-bound CSRF token.
 *
 * Pre-M1 this class returned hash('sha256', $configSalt . $_SESSION['id']),
 * which produced the *same* value for the entire session and depended only on
 * a single global salt — predictable for any attacker who could read the
 * session id (e.g. via a leaked URL or XSS once). Since M1 the token is a
 * cryptographically-random 32-byte value (64 hex chars) generated on first
 * use, persisted in $_SESSION, and rotated on login by LoginView.
 *
 * A future milestone (M2) will swap this for paragonie/anti-csrf, which
 * issues a unique token per form submission. Until then a single token per
 * session is the practical upgrade that keeps existing JS/PHP wiring intact.
 */
final class CSRFtoken
{
    private const SESSION_KEY = '__saso_csrftoken';

    /**
     * Return the current session's CSRF token, generating one on first use.
     */
    public static function current(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Constant-time compare a user-supplied token against the session value.
     */
    public static function verify(string $candidate): bool
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $candidate);
    }

    /**
     * Discard the current token so the next current() call returns a fresh one.
     * Called by LoginView after session_regenerate_id() to bind the token to
     * the new session.
     */
    public static function rotate(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @deprecated since M1 — calls current() and ignores the salt argument.
     *             Retained so existing config.json files with `csrftokensalt`
     *             keep working through the migration window.
     * @param string $salt Intentionally unused; kept for signature compat.
     */
    public static function salting(string $salt): string
    {
        unset($salt);
        return self::current();
    }
}
