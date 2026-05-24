<?php

declare(strict_types=1);

namespace Saso\Application\Auth;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Saso\Domain\Auth\Exception\InvalidCredentialsException;
use saso\entity\Member;

/**
 * Username + password verification, shared by the legacy `/auth/start/` form
 * flow and the REST `/api/v1/auth/login` endpoint.
 *
 * Responsibilities:
 *   - Look up a `Member` row by login id (case-sensitive, exact match).
 *   - Verify the raw password against the stored hash via
 *     {@see \saso\entity\Member::verifyPassword()} — that helper accepts
 *     both modern Argon2id hashes and the legacy SHA-256 chain.
 *   - Optionally re-hash the stored credential to Argon2id when the legacy
 *     `LoginUsecase` would have re-hashed it. Failures are swallowed (the
 *     login itself still succeeds) so a transient DB error never blocks a
 *     legitimate sign-in.
 *
 * The service deliberately produces the same indistinguishable
 * {@see InvalidCredentialsException} for "no such user" and "wrong
 * password" to prevent user-enumeration.
 *
 * Why not call into `LoginUsecase` directly: that class is bound to the
 * legacy monadic Finder/Updater/Presenter pipeline and mutates `$_SESSION`
 * as a side effect. The REST flow needs neither the session mutation nor
 * the redirect-target output. This service factors out the password
 * verification primitive so both paths share the exact same check
 * (including the legacy hash fallback + rehash-on-success behaviour) while
 * the calling controllers keep their own concerns.
 *
 * @phpstan-type MemberRow array{id: string, password: string, userName: string}
 */
final class VerifyCredentialsService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly PDO $pdo,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Verify a username / password pair and return a small DTO describing
     * the authenticated member.
     *
     * @throws InvalidCredentialsException when no member matches, when the
     *                                     password does not match, or when
     *                                     the username does not pass the
     *                                     legacy id constraint
     *
     * @return array{id: string, name: string}
     */
    public function verify(string $username, string $password): array
    {
        $idCheck = Member::idConstraint($username);
        if ($idCheck->isLeft()) {
            // Username failed structural validation. The legacy
            // `Member::idConstraint` rejects strings outside `[A-Za-z0-9_-]`
            // or shorter than 8 chars — we collapse this into the generic
            // 401 response so callers cannot probe valid usernames by
            // structural error vs. credential error.
            throw new InvalidCredentialsException();
        }

        $row = $this->loadRow($username);
        if ($row === null) {
            throw new InvalidCredentialsException();
        }

        if (!Member::verifyPassword($password, $row['password'])) {
            throw new InvalidCredentialsException();
        }

        // Best-effort rehash of legacy SHA-256 chains. Mirrors the behaviour
        // of LoginUsecase::maybeRehash — a transient DB error is swallowed
        // so the login still succeeds.
        if (Member::needsRehash($row['password'])) {
            try {
                $upgrade = $this->pdo->prepare(
                    'UPDATE Member SET password = :password WHERE id = :id',
                );
                $upgrade->bindValue('password', Member::hashPassword($password));
                $upgrade->bindValue('id', $row['id']);
                $upgrade->execute();
            } catch (\Throwable $e) {
                // Intentionally swallowed — see docstring. Log so the
                // operator can correlate hash-upgrade failures with a
                // legitimate login that nevertheless still succeeded.
                // The raw password is never included in the context.
                $this->logger->warning(
                    'VerifyCredentialsService: password rehash UPDATE failed; login allowed.',
                    [
                        'memberId' => $row['id'],
                        'error'    => $e->getMessage(),
                    ],
                );
            }
        }

        return [
            'id'   => $row['id'],
            'name' => $row['userName'],
        ];
    }

    /**
     * Update the stored password hash to a fresh Argon2id digest of the
     * supplied raw password. The caller is responsible for verifying the
     * caller's CURRENT password first (e.g. via {@see verify()}).
     *
     * @throws \RuntimeException when the UPDATE affects no rows
     */
    public function updatePasswordHash(string $memberId, string $newPasswordPlain): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE Member SET password = :password WHERE id = :id',
        );
        $stmt->bindValue('password', Member::hashPassword($newPasswordPlain));
        $stmt->bindValue('id', $memberId);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException(sprintf(
                'VerifyCredentialsService: no Member row updated for id %s.',
                $memberId,
            ));
        }
    }

    /**
     * @return MemberRow|null
     */
    private function loadRow(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, password, userName FROM Member WHERE id = :id LIMIT 1',
        );
        $stmt->bindValue('id', $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        /** @var MemberRow $row */
        return $row;
    }
}
