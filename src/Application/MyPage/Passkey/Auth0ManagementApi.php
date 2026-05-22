<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

/**
 * Minimal contract for the Auth0 Management API calls SASO needs to manage
 * a user's passkeys. Kept as an interface so unit tests can substitute a
 * fixture implementation without staging HTTP fixtures.
 *
 * The concrete implementation lives in
 * {@see \Saso\Infrastructure\Auth\GuzzleAuth0ManagementApi}.
 */
interface Auth0ManagementApi
{
    /**
     * Returns the user's authentication methods filtered down to
     * passkey-style WebAuthn entries (`passkey`, `webauthn-roaming`,
     * `webauthn-platform`). Other factor types (OTP, SMS, email) are
     * stripped before the result is handed to the My Page view.
     *
     * @return list<Auth0Passkey>
     *
     * @throws Auth0ManagementApiException on network failure, 4xx/5xx, or
     *                                     unparseable response shape
     */
    public function listPasskeys(string $auth0UserId): array;

    /**
     * Removes one passkey from the Auth0 user record. Idempotent — a 404
     * from Auth0 (already deleted) is treated as success so the UI does
     * not lock up when two browser tabs race on the same passkey.
     *
     * @throws Auth0ManagementApiException on network failure or 5xx
     */
    public function deletePasskey(string $auth0UserId, string $authenticationMethodId): void;
}
