<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Repository;

use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;

/**
 * Read/write contract for `auth_provider` rows (cf. ADR 0003).
 *
 * Concrete implementations decrypt `client_secret_encrypted` before
 * returning a record; writes encrypt on the way back. Domain code
 * therefore sees plaintext secrets but persistence stores ciphertext.
 *
 * Returned lists are sorted as the login screen expects them:
 * `is_default DESC, name ASC` — operators can mark a "preferred" IdP
 * that floats to the top of the buttons row.
 */
interface AuthProviderRepository
{
    public function findById(AuthProviderId $id): ?AuthProviderRecord;

    /**
     * @return list<AuthProviderRecord>
     */
    public function listAll(): array;

    /**
     * @return list<AuthProviderRecord>
     */
    public function listEnabled(): array;

    public function save(AuthProviderRecord $record): AuthProviderRecord;

    public function delete(AuthProviderId $id): void;
}
