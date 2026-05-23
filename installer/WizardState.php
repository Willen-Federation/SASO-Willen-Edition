<?php

declare(strict_types=1);

namespace saso\installer;

use saso\util\EnvLoader;
use saso\util\EnvWriter;

/**
 * Read-only snapshot of the install wizard's progress, derived from the
 * filesystem rather than the session. Every step inspects the current
 * state of `.env`, `installer.json`, and the database connectivity to
 * decide whether the prerequisite work is done.
 *
 * Keeping state on disk (instead of session) means a refresh, a separate
 * tab, or a different operator can resume installation cleanly.
 */
final class WizardState
{
    public const STEP_WELCOME  = 'welcome';
    public const STEP_DATABASE = 'database';
    public const STEP_SCHEMA   = 'schema';
    public const STEP_SECURITY = 'security';
    public const STEP_SERVICES = 'services';
    public const STEP_ADMIN    = 'admin';
    public const STEP_DONE     = 'done';

    /** @return list<array{key: string, label: string}> */
    public static function steps(): array
    {
        return [
            ['key' => self::STEP_WELCOME,  'label' => 'ようこそ'],
            ['key' => self::STEP_DATABASE, 'label' => 'データベース'],
            ['key' => self::STEP_SCHEMA,   'label' => 'スキーマ作成'],
            ['key' => self::STEP_SECURITY, 'label' => 'セキュリティ'],
            ['key' => self::STEP_SERVICES, 'label' => '外部サービス'],
            ['key' => self::STEP_ADMIN,    'label' => '管理者作成'],
        ];
    }

    public static function envPath(): string
    {
        return __DIR__ . '/../.env';
    }

    public static function envExamplePath(): string
    {
        return __DIR__ . '/../.env.example';
    }

    public static function installerJsonPath(): string
    {
        return __DIR__ . '/installer.json';
    }

    /** @return array<string, string> */
    public static function loadEnv(): array
    {
        return EnvLoader::loadFile(self::envPath());
    }

    public static function envHasDb(array $env): bool
    {
        $dsn  = $env['DB_DSN']      ?? '';
        $user = $env['DB_USER']     ?? '';
        return $dsn !== '' && $user !== '';
    }

    public static function envHasSecurity(array $env): bool
    {
        $appKey = $env['APP_KEY'] ?? '';
        return $appKey !== '' && strlen($appKey) >= 32;
    }

    public static function schemaInstalled(\PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'Member'");
            return $stmt !== false && $stmt->fetchColumn() !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function adminExists(\PDO $pdo): bool
    {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM Member");
            if ($stmt === false) {
                return false;
            }
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Build a connection from the current `.env`. Returns null if the
     * connection fails (caller decides how to render the error).
     */
    public static function tryConnect(array $env): ?\PDO
    {
        $dsn  = $env['DB_DSN']      ?? '';
        $user = $env['DB_USER']     ?? null;
        $pass = $env['DB_PASSWORD'] ?? '';
        if ($dsn === '') {
            return null;
        }
        try {
            return new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Best-effort generation of an APP_KEY (base64-encoded 32 bytes).
     */
    public static function generateAppKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    public static function generateHexSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Ensure a `.env` file exists; on first run we copy the bundled
     * `.env.example` so the operator's edits don't lose the inline
     * documentation.
     */
    public static function ensureEnvFile(): bool
    {
        $envPath = self::envPath();
        if (is_file($envPath)) {
            return true;
        }
        $example = self::envExamplePath();
        if (!is_file($example)) {
            return @file_put_contents($envPath, "# SASO\n") !== false;
        }
        $contents = @file_get_contents($example);
        if ($contents === false) {
            return false;
        }
        if (@file_put_contents($envPath, $contents) === false) {
            return false;
        }
        @chmod($envPath, 0640);
        return true;
    }

    /**
     * Determine the next step to land on. Walks the prerequisites in
     * order and returns the first one that isn't satisfied.
     */
    public static function nextStep(): string
    {
        $env = self::loadEnv();
        if (!self::envHasDb($env)) {
            return self::STEP_DATABASE;
        }
        $pdo = self::tryConnect($env);
        if ($pdo === null) {
            return self::STEP_DATABASE;
        }
        if (!self::schemaInstalled($pdo)) {
            return self::STEP_SCHEMA;
        }
        if (!self::envHasSecurity($env)) {
            return self::STEP_SECURITY;
        }
        if (!self::adminExists($pdo)) {
            return self::STEP_ADMIN;
        }
        return self::STEP_DONE;
    }

    /**
     * Persist DB credentials to `.env`.
     */
    public static function writeDbConfig(string $dsn, string $user, string $password): bool
    {
        if (!self::ensureEnvFile()) {
            return false;
        }
        return EnvWriter::setMany(self::envPath(), [
            'DB_DSN'      => $dsn,
            'DB_USER'     => $user,
            'DB_PASSWORD' => $password,
        ]);
    }

    /**
     * Persist application security keys to `.env`. Empty values are
     * skipped so the operator can leave the auto-generated defaults
     * untouched on repeated visits.
     *
     * @param array<string, string> $values
     */
    public static function writeSecurity(array $values): bool
    {
        if (!self::ensureEnvFile()) {
            return false;
        }
        $filtered = [];
        foreach ($values as $key => $value) {
            if (trim($value) === '') {
                continue;
            }
            $filtered[$key] = $value;
        }
        if (empty($filtered)) {
            return true;
        }
        return EnvWriter::setMany(self::envPath(), $filtered);
    }

    /**
     * Remove `installer/installer.json` so subsequent visits stop
     * redirecting into the wizard. Idempotent.
     */
    public static function lockInstaller(): bool
    {
        $path = self::installerJsonPath();
        if (!is_file($path)) {
            return true;
        }
        return @unlink($path);
    }

    /**
     * Recursively delete the installer directory. Used by the optional
     * "delete installer folder" cleanup step on the final page.
     */
    public static function deleteInstallerDir(): bool
    {
        $dir = __DIR__;
        return self::rmrf($dir);
    }

    private static function rmrf(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path) && !is_link($path)) {
                if (!self::rmrf($path)) {
                    return false;
                }
            } else {
                if (!@unlink($path)) {
                    return false;
                }
            }
        }
        return @rmdir($dir);
    }
}
