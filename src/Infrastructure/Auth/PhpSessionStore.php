<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth;

use Auth0\SDK\Contract\StoreInterface;

/**
 * Thin $_SESSION adapter for the Auth0 PHP SDK.
 *
 * Auth0\SDK\Store\SessionStore requires an already-built SdkConfiguration in
 * its constructor (circular dependency when we also pass it as transientStorage
 * or sessionStorage). This class breaks that cycle by implementing StoreInterface
 * directly against $_SESSION with a configurable key prefix.
 *
 * Assumes session_start() has already been called (index.php does this).
 */
final class PhpSessionStore implements StoreInterface
{
    public function __construct(private readonly string $prefix = 'auth0_sdk') {}

    public function defer(bool $deferring): void {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$this->prefix . '_' . $key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$this->prefix . '_' . $key] = $value;
    }

    public function delete(string $key): void
    {
        unset($_SESSION[$this->prefix . '_' . $key]);
    }

    public function purge(): void
    {
        $pfx = $this->prefix . '_';
        foreach (array_keys($_SESSION ?? []) as $k) {
            if (str_starts_with((string) $k, $pfx)) {
                unset($_SESSION[$k]);
            }
        }
    }
}
