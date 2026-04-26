<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Widen `Member.password` from VARCHAR(80) to VARCHAR(255) to fit Argon2id
 * digests produced by `password_hash(PASSWORD_ARGON2ID)`.
 *
 * Pre-M1 the column was sized for the legacy SHA256 hex chain (64 chars).
 * Argon2id digests are typically ~95 chars and the format is allowed to
 * grow as PHP tunes parameters, so we move to the conventional safe
 * ceiling of 255.
 *
 * Existing rows keep their legacy hashes — `Member::needsRehash()` plus
 * `LoginUsecase::maybeRehash()` upgrade them to Argon2id transparently
 * on next successful login (see M1 changelog).
 *
 * Replaces the hand-applied
 * `migrations/M1_001_widen_password_column.sql` file shipped before
 * M4-B introduced Phinx (cf. ADR 0007).
 */
final class WidenPasswordColumn extends AbstractMigration
{
    public function up(): void
    {
        $this->table('Member')
            ->changeColumn('password', 'string', [
                'limit' => 255,
                'null'  => false,
            ])
            ->update();
    }

    public function down(): void
    {
        // Narrowing back to 80 would silently truncate Argon2id digests.
        // Treating this migration as one-way is the safer choice; the
        // operator can always run a one-shot script if a true rollback
        // becomes necessary.
        throw new \Phinx\Migration\IrreversibleMigrationException(
            'Cannot reverse WidenPasswordColumn — narrowing the column would corrupt stored Argon2id hashes.',
        );
    }
}
