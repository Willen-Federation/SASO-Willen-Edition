<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth\Provider;

use PDO;
use Saso\Domain\Auth\AuthenticatedIdentity;
use Saso\Domain\Auth\AuthProvider;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\CallbackRequest;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;
use Saso\Domain\Auth\LoginContext;
use Saso\Domain\Auth\LogoutContext;
use Saso\Domain\Auth\Redirect;

/**
 * Local Argon2id (with transparent SHA-256 legacy upgrade) provider.
 *
 * Bridges the new {@see AuthProvider} contract to the legacy `Member` table
 * so the same orchestrator can drive both IdP-based and local sign-in. The
 * legacy `auth/LoginUsecase.php` continues to serve the existing username +
 * password form unchanged; this provider exists for the new admin UI flow
 * where the operator clicks a "Sign in with the local account" button just
 * like any IdP button.
 *
 * `completeLogin()` accepts the form fields under
 * {@see CallbackRequest::$body} as `id` + `password`. Verification is
 * performed by the same code paths the legacy usecase uses
 * (`legacy entity\Member::verifyPassword`), so behaviour is identical.
 */
final class LocalProvider implements AuthProvider
{
    /** Stable id we use for the local provider when no DB row exists yet. */
    public const VIRTUAL_ID = 1;

    public function __construct(
        private readonly AuthProviderRecord $record,
        private readonly PDO $pdo,
        private readonly string $loginUrl = '/auth/start',
    ) {
        if ($record->type !== AuthProviderType::Local) {
            throw ProviderMisconfiguredException::for(
                $record->name,
                'LocalProvider requires AuthProviderType::Local.',
            );
        }
    }

    public function id(): AuthProviderId
    {
        return $this->record->id;
    }

    public function type(): AuthProviderType
    {
        return $this->record->type;
    }

    public function displayName(): string
    {
        return $this->record->name;
    }

    public function beginLogin(LoginContext $context): Redirect
    {
        $_SESSION['auth.return_to']   = $context->returnTo;
        $_SESSION['auth.provider_id'] = $this->record->id->value;
        return new Redirect($this->loginUrl, 302);
    }

    public function completeLogin(CallbackRequest $request): AuthenticatedIdentity
    {
        $id       = trim((string) ($request->body['id']       ?? ''));
        $password =        (string) ($request->body['password'] ?? '');

        if ($id === '' || $password === '') {
            throw AuthFailedException::invalidCredentials('Missing id or password.');
        }

        $stmt = $this->pdo->prepare('SELECT id, password, userName FROM Member WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        /** @var array{id?: string, password?: string, userName?: string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false || !isset($row['password'])) {
            throw AuthFailedException::invalidCredentials();
        }

        $stored = (string) $row['password'];
        $verified = $stored !== '' && str_starts_with($stored, '$')
            ? password_verify($password, $stored)
            : hash_equals(self::legacyHashed($password), $stored);

        if (!$verified) {
            throw AuthFailedException::invalidCredentials();
        }

        // Best-effort rehash to Argon2id when the stored hash is legacy SHA256
        // or another non-Argon2id format. Failures are silent — they cannot
        // block the login.
        if ($stored === '' || !str_starts_with($stored, '$') || password_needs_rehash($stored, PASSWORD_ARGON2ID)) {
            try {
                $upgraded = password_hash($password, PASSWORD_ARGON2ID);
                $upd = $this->pdo->prepare('UPDATE Member SET password = :hash WHERE id = :id');
                $upd->bindValue(':hash', $upgraded);
                $upd->bindValue(':id', $row['id'] ?? $id);
                $upd->execute();
            } catch (\Throwable) {
                // ignore — verification succeeded, upgrade is opportunistic
            }
        }

        $name = (string) ($row['userName'] ?? ($row['id'] ?? $id));

        return new AuthenticatedIdentity(
            authProviderId: $this->record->id,
            externalSubject: 'local:'.($row['id'] ?? $id),
            email: '',
            displayName: $name,
            claims: [
                'id' => (string) ($row['id'] ?? $id),
                'name' => $name,
            ],
        );
    }

    public function supportsLogout(): bool
    {
        return false;
    }

    public function beginLogout(LogoutContext $context): ?Redirect
    {
        return null;
    }

    /**
     * Legacy (pre-M1) password hash. Kept private and read-only — used only
     * to verify ancient rows so the user can sign in once and trigger the
     * Argon2id upgrade above.
     */
    private static function legacyHashed(string $raw): string
    {
        $hashed = hash('sha256', $raw);
        $salted = 'stok-administra_sistemo'.$hashed.'plej_simpla';
        return array_reduce(
            range(1, 1000),
            fn (string $carry, int $item): string => hash('sha256', $carry),
            $salted,
        );
    }
}
