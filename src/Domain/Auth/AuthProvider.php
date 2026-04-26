<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;

/**
 * Pluggable identity provider contract (cf. ADR 0003).
 *
 * Every authentication source implements this interface — the legacy
 * username + password store (`LocalProvider`), OpenID Connect identity
 * platforms (`OidcProvider`, wrapping jumbojett/openid-connect-php), and
 * SAML 2.0 IdPs (`SamlProvider`, wrapping onelogin/php-saml). Concrete
 * implementations are constructed from a row in the `auth_provider` DB
 * table (M4); the interface is stable so the rest of the application
 * does not depend on which protocol is in use.
 *
 * Lifecycle, OIDC / SAML edition:
 *
 * 1. The application calls {@see beginLogin()} with a fresh state token,
 *    nonce, and the URL the user should land on after sign-in. The
 *    provider returns a {@see Redirect} to the IdP. State / nonce are
 *    stashed in `$_SESSION` so the callback can verify them.
 * 2. The IdP redirects the browser back to the application's callback
 *    URL with `code` + `state` (OIDC) or `SAMLResponse` (SAML).
 * 3. The application builds a {@see CallbackRequest} from the inbound
 *    HTTP request and hands it to {@see completeLogin()}, which
 *    validates the message, exchanges the code for tokens (OIDC), and
 *    returns an {@see AuthenticatedIdentity}.
 * 4. The application looks the identity up in `member_external_identity`
 *    or auto-provisions a new `Member` (M4).
 *
 * Lifecycle, `LocalProvider`:
 *
 * 1. {@see beginLogin()} returns a redirect to the local login form path.
 * 2. The user submits the form. The application packages the POST into a
 *    {@see CallbackRequest} and calls {@see completeLogin()}, which
 *    verifies the password against the stored Argon2id hash and returns
 *    an {@see AuthenticatedIdentity} whose `externalSubject` is the
 *    member's local id.
 *
 * Implementations MUST:
 *
 *   * verify state, nonce, and signature before returning an identity;
 *   * throw {@see AuthFailedException} on any verification failure (the
 *     handler renders the matching `SASO-AUTH-1xxx` problem);
 *   * throw {@see ProviderMisconfiguredException} from the constructor
 *     when the row's configuration is incomplete or unreachable.
 */
interface AuthProvider
{
    public function id(): AuthProviderId;

    public function type(): AuthProviderType;

    public function displayName(): string;

    /**
     * Begins a new login. Returns a {@see Redirect} to the IdP (or to
     * the local login form, for {@see Provider\LocalProvider}).
     *
     * @throws ProviderMisconfiguredException if the provider's stored
     *                                        configuration cannot drive
     *                                        a redirect (missing client
     *                                        id, unreachable discovery
     *                                        endpoint, …)
     */
    public function beginLogin(LoginContext $context): Redirect;

    /**
     * Completes a login from an IdP callback (or a local form POST).
     *
     * @throws AuthFailedException if the message fails any
     *                             verification step
     * @throws ProviderMisconfiguredException if the configuration the
     *                                        constructor accepted is no
     *                                        longer usable (e.g. the
     *                                        JWKs endpoint started
     *                                        404ing)
     */
    public function completeLogin(CallbackRequest $request): AuthenticatedIdentity;

    /**
     * Whether the provider supports IdP-initiated single logout.
     *
     * `LocalProvider` returns `false` (the application clears its own
     * session). OIDC providers return `true` when the IdP advertises an
     * `end_session_endpoint`; SAML providers return `true` when the
     * IdP's metadata declares a SingleLogoutService.
     */
    public function supportsLogout(): bool;

    /**
     * Returns a redirect to the IdP's logout endpoint, or `null` if
     * single-logout is unsupported (`supportsLogout() === false`) — in
     * which case the caller terminates the local session and the user
     * stays signed in at the IdP.
     */
    public function beginLogout(LogoutContext $context): ?Redirect;
}
