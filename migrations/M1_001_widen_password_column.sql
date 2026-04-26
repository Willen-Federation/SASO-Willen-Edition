-- =============================================================================
-- Migration: M1_001 — Widen Member.password to fit Argon2id digests
-- =============================================================================
-- Pre-M1, the column was VARCHAR(80) which fit the legacy SHA256 chain (64 hex
-- chars). Argon2id digests produced by password_hash() are typically ~95 chars
-- and the format is allowed to grow as PHP tunes parameters, so we move to 255
-- (the conventional safe ceiling for password_hash() output).
--
-- Apply once to existing deployments BEFORE the M1 release rolls out. Newly
-- installed instances are created with the correct width by
-- installer/createTables.php and do not need this migration.
--
-- Idempotent: re-running is safe; ALTER TABLE … MODIFY widens but does not
-- truncate existing values.
-- =============================================================================

ALTER TABLE Member MODIFY password VARCHAR(255) NOT NULL;

-- After this migration, existing users keep their legacy SHA256 hashes and can
-- still log in. On their next successful login, Member::needsRehash() detects
-- the legacy format and LoginUsecase::maybeRehash() upgrades the row to
-- Argon2id transparently.
