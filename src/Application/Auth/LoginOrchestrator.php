<?php

declare(strict_types=1);

namespace Saso\Application\Auth;

use DateTimeImmutable;
use PDO;
use Saso\Domain\Auth\AuthenticatedIdentity;
use Saso\Domain\Auth\AuthProvider;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\CallbackRequest;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\ExternalIdentity;
use Saso\Domain\Auth\LoginContext;
use Saso\Domain\Auth\LogoutContext;
use Saso\Domain\Auth\Redirect;
use Saso\Domain\Auth\Repository\ExternalIdentityRepository;
use Saso\Infrastructure\Auth\AuthProviderFactory;

/**
 * Application-level orchestrator for IdP-driven sign-in.
 *
 * Owns the steps that the {@see AuthProvider} contract intentionally does
 * not: state/nonce minting, just-in-time member provisioning, session
 * regeneration, and the optional return-to bounce after callback.
 *
 * The legacy `auth/LoginUsecase.php` continues to handle the username +
 * password form; this orchestrator powers the new "Sign in with Auth0 /
 * Cognito / Firebase / Microsoft Entra / SAML / Local" buttons.
 */
final class LoginOrchestrator
{
    public function __construct(
        private readonly AuthProviderFactory $providers,
        private readonly ExternalIdentityRepository $externalIdentities,
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Begin a new login. Returns the URL to redirect the browser to.
     */
    public function beginLogin(AuthProviderId $providerId, string $returnTo): Redirect
    {
        $provider = $this->providers->forId($providerId);

        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        // Persist the provider ID and return URL in the session so handleCallback()
        // can retrieve them after the IdP redirect. This survives the OAuth
        // round-trip, avoids exposing the provider ID in the callback URL (security),
        // and allows the post-login redirect to work.
        $_SESSION['auth.provider_id'] = $providerId->value;
        $_SESSION['auth.return_to'] = $returnTo;

        return $provider->beginLogin(new LoginContext(
            returnTo:        $returnTo,
            csrfStateToken:  $state,
            nonce:           $nonce,
        ));
    }

    /**
     * Handle the IdP callback, JIT-provision the member if necessary, and
     * write the legacy session keys so the rest of the application sees an
     * authenticated user.
     *
     * @return string the URL the browser should land on after sign-in
     */
    public function handleCallback(AuthProviderId $providerId, CallbackRequest $request): string
    {
        $provider = $this->providers->forId($providerId);
        $identity = $provider->completeLogin($request);

        $memberId = $this->resolveMember($identity);

        // Regenerate the session id to defend against fixation, then write
        // the legacy keys other code paths read ($_SESSION['id']/['userName']
        // existed pre-M3 and the rest of the app keys off them).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['id']                = $memberId;
        $_SESSION['userName']          = $identity->displayName !== '' ? $identity->displayName : $identity->email;
        $_SESSION['time']              = time();
        $_SESSION['provider_id']       = $providerId->value;
        $_SESSION['external_subject']  = $identity->externalSubject;

        $this->externalIdentities->recordLogin($providerId, $identity->externalSubject);

        $returnTo = (string) ($_SESSION['auth.return_to'] ?? '/');
        unset(
            $_SESSION['auth.state'],
            $_SESSION['auth.nonce'],
            $_SESSION['auth.return_to'],
            $_SESSION['auth.provider_id'],
        );
        return $returnTo === '' ? '/' : $returnTo;
    }

    /**
     * Begin a logout. Returns a Redirect to the IdP's logout endpoint, or
     * `null` if single-logout is unsupported (caller terminates the local
     * session and shows a generic "signed out" page).
     */
    public function beginLogout(string $returnTo): ?Redirect
    {
        $providerIdValue = $_SESSION['provider_id'] ?? null;
        if (!is_int($providerIdValue) || $providerIdValue < 1) {
            return null;
        }
        try {
            $provider = $this->providers->forId(new AuthProviderId($providerIdValue));
        } catch (\Throwable) {
            return null;
        }
        if (!$provider->supportsLogout()) {
            return null;
        }
        $idTokenHint = isset($_SESSION['auth.id_token']) && is_string($_SESSION['auth.id_token'])
            ? $_SESSION['auth.id_token']
            : null;
        return $provider->beginLogout(new LogoutContext($returnTo, $idTokenHint));
    }

    /**
     * JIT provisioning. If a member is already linked to this external
     * identity, return its id. Otherwise, link to a local member matched by
     * email, or create a new member when no match exists.
     */
    private function resolveMember(AuthenticatedIdentity $identity): int
    {
        $existing = $this->externalIdentities->find(
            $identity->authProviderId,
            $identity->externalSubject,
        );
        if ($existing !== null) {
            return $existing->memberId;
        }

        $memberId = $this->findLocalMemberIdByEmail($identity->email);
        if ($memberId === null) {
            $memberId = $this->createJitMember($identity);
        }

        $now = new DateTimeImmutable();
        $this->externalIdentities->link(new ExternalIdentity(
            memberId:        $memberId,
            authProviderId:  $identity->authProviderId,
            externalSubject: $identity->externalSubject,
            createdAt:       $now,
            updatedAt:       $now,
            lastLoginAt:     $now,
        ));

        return $memberId;
    }

    /**
     * The legacy `Member` table uses a string `id` column (login id) and
     * does not store an integer surrogate key. To bridge to the FK shape
     * `member_external_identity.member_id BIGINT`, we hash the string id
     * deterministically into a positive 64-bit integer. The hash is stable
     * for the lifetime of a local member id.
     */
    private function findLocalMemberIdByEmail(string $email): ?int
    {
        if ($email === '') {
            return null;
        }
        // Best-effort: legacy Member table does not have an email column in
        // every deployment, so this query is run with a try/catch and skipped
        // on schema mismatch. Implementations that have added an `email`
        // column will resolve correctly.
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM Member WHERE id = :email LIMIT 1');
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            /** @var array{id?: string}|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false && isset($row['id'])) {
                return self::hashLegacyId((string) $row['id']);
            }
        } catch (\Throwable) {
            // schema does not have email; fall through to JIT create
        }
        return null;
    }

    private function createJitMember(AuthenticatedIdentity $identity): int
    {
        // Keep within the legacy id constraint regex: `^[0-9a-zA-Z-_]{8,20}$`.
        $base = preg_replace('/[^0-9A-Za-z_-]/', '_', $identity->email !== '' ? $identity->email : $identity->displayName) ?? 'idp_user';
        $base = substr($base, 0, 12);
        if (strlen($base) < 8) {
            $base = str_pad($base, 8, '0');
        }
        $candidate = $base.'_'.substr(bin2hex(random_bytes(3)), 0, 6);
        $candidate = substr($candidate, 0, 20);

        $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID);
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO Member (id, userName, password) VALUES (:id, :name, :password)'
            );
            $stmt->bindValue(':id', $candidate);
            $stmt->bindValue(':name', $identity->displayName !== '' ? $identity->displayName : $candidate);
            $stmt->bindValue(':password', $randomPassword);
            $stmt->execute();
        } catch (\Throwable) {
            // Schema or duplicate-id collision — re-throw as auth failure so
            // the operator notices in the next reload rather than silently
            // looping the IdP.
            throw AuthFailedException::callbackInvalid('JIT member provisioning failed.');
        }
        return self::hashLegacyId($candidate);
    }

    /**
     * Deterministic, positive 64-bit integer derived from the legacy string
     * `Member.id`. PHPStan keeps `int` here even though we mask 63 bits.
     */
    private static function hashLegacyId(string $localId): int
    {
        // Hash and mask to 63 bits to stay within PHP's signed int range.
        $hex  = substr(hash('sha256', $localId), 0, 16);
        $high = (int) hexdec(substr($hex, 0, 8));
        $low  = (int) hexdec(substr($hex, 8, 8));
        $val  = (($high & 0x7fffffff) << 32) | ($low & 0xffffffff);
        return $val < 1 ? 1 : $val;
    }
}
