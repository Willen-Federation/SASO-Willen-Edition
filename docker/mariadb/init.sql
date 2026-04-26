-- =============================================================================
-- MariaDB seed for the dev container.
-- -----------------------------------------------------------------------------
-- The MARIADB_DATABASE / MARIADB_USER / MARIADB_PASSWORD env vars in
-- docker-compose.yml already create the database and the application user
-- when the container starts with an empty data volume, so this file only
-- exists to (a) explicitly set a UTF-8 collation and (b) reserve a place for
-- future seed fixtures.
--
-- The application schema itself is created by the web installer
-- (`/installer/start`) or, after M2-A, by the manual SQL migrations under
-- `migrations/`. Phinx replaces both in M4.
-- =============================================================================

ALTER DATABASE saso_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
