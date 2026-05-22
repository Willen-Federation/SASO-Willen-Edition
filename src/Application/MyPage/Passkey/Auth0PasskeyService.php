<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

/**
 * Application service composing the Auth0 ProviderLookup with the
 * Management API client.
 *
 * The DI containers (`MyPageUsecase`, `PasskeyDeleteDIContainer`) consume
 * this — they never talk to the HTTP layer directly. This keeps the
 * "user → SASO member id → Auth0 sub → Auth0 authentication_method_id"
 * resolution in one place, and lets the rest of the code treat passkeys
 * as a simple list-and-delete resource.
 */
final class Auth0PasskeyService
{
    public function __construct(
        private readonly Auth0ProviderLookup $lookup,
        private readonly Auth0ManagementApi $api,
    ) {
    }

    /**
     * Returns the user's passkeys, or `[]` when the member is not linked
     * to Auth0 (a perfectly normal state — they sign in with a local
     * password or a different IdP).
     *
     * Network/Auth0 failures bubble up as {@see Auth0ManagementApiException}
     * so the caller can render a "transient failure" banner instead of
     * silently hiding the list.
     *
     * @return list<Auth0Passkey>
     */
    public function listFor(string $memberId): array
    {
        $link = $this->lookup->findFor($memberId);
        if ($link === null) {
            return [];
        }

        return $this->api->listPasskeys($link->externalSubject);
    }

    /**
     * Deletes one passkey owned by the member. Returns `true` when the
     * delete (or the no-op for an already-removed passkey) succeeded, or
     * `false` when the member is not linked to Auth0.
     *
     * @throws Auth0ManagementApiException on Auth0 failure
     */
    public function deleteFor(string $memberId, string $authenticationMethodId): bool
    {
        $link = $this->lookup->findFor($memberId);
        if ($link === null) {
            return false;
        }
        $this->api->deletePasskey($link->externalSubject, $authenticationMethodId);
        return true;
    }
}
