<?php
namespace saso;

require_once __DIR__.'/util/EnvLoader.php';

use saso\util\EnvLoader;

final class ConfigLoader
{
    private static $configFile;

    /**
     * Load and normalize the runtime configuration.
     *
     * Since M1 the loader overlays values from a sibling `.env` file on top of
     * config.json so that secrets (DB credentials in particular) never need to
     * sit in a JSON blob that operators tend to upload via FTP / cPanel and
     * occasionally serve back through misconfigured web roots. Only a small
     * allow-list of keys is overlay-able; everything else stays in config.json
     * (and, after M4, in the system_setting DB table editable via Web UI).
     *
     * Resolution order for an overlay-able key (highest first):
     *   1. `.env` file in the same directory
     *   2. real environment variable (getenv) — useful for Docker / CI
     *   3. value from config.json
     */
    public static function load(string $relative=''): array
    {
        $env = defined('saso\ENV') ? \saso\ENV : (defined('ENV') ? ENV : null);
        if(empty(self::$configFile)) {
            self::$configFile = $env===null?$relative.'config.json':$relative.'config_'.$env.'.json';
        }
        $config = json_decode(file_get_contents(self::$configFile), true);
        $env = EnvLoader::loadFile($relative.'.env');
        // Populate PHP's environment variables from .env so getenv() calls work
        foreach ($env as $key => $value) {
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
        $config = self::overlayEnv($config, $env);
        $config = self::overlayDb($config);
        return self::regularization($config);
    }

    public static function regularization(array $config): array
    {
        $config['documentRoot'] = '/'.trim($config['documentRoot'], '/').'/';

        // Fallback only for the hardcoded production path: if config.json was never edited
        // from the default, silently replace it with the real project root so fresh clones
        // and dev environments boot without manual edits. Any explicit value — whether from
        // config.json or from the APP_DOCUMENT_ROOT env override — is kept as-is, even if
        // the directory does not exist on this machine (e.g., a Docker path validated in CI).
        $productionPath = '/home/schicksal/domains/saso.sksl.jp/public_html/';
        if ($config['documentRoot'] === $productionPath) {
            $config['documentRoot'] = __DIR__.'/';
        }

        $programDirTrimmed = trim($config['programDir'], '/');
        $config['programDir'] = $programDirTrimmed === '' ? '' : $programDirTrimmed.'/';
        $config['https'] = $config['https']===true?true:false;
        $config['logPath'] = '/'.trim($config['logPath'], '/').'/';
        return $config;
    }

    /**
     * Overlay the small allow-list of overlay-able keys from $env onto $config.
     * Anything not listed here is intentionally not overridable from .env so
     * that a leaked .env cannot silently change application paths or feature
     * toggles.
     */
    private static function overlayEnv(array $config, array $env): array
    {
        if (!isset($config['database']) || !is_array($config['database'])) {
            $config['database'] = [];
        }
        $dsn = EnvLoader::get($env, 'DB_DSN');
        if ($dsn !== null) {
            $config['database']['dsn'] = $dsn;
        }
        $user = EnvLoader::get($env, 'DB_USER');
        if ($user !== null) {
            $config['database']['user'] = $user;
        }
        $password = EnvLoader::get($env, 'DB_PASSWORD');
        if ($password !== null) {
            $config['database']['password'] = $password;
        }
        $https = EnvLoader::get($env, 'APP_HTTPS');
        if ($https !== null) {
            $config['https'] = filter_var($https, FILTER_VALIDATE_BOOLEAN);
        }
        $documentRoot = EnvLoader::get($env, 'APP_DOCUMENT_ROOT');
        if ($documentRoot !== null) {
            $config['documentRoot'] = $documentRoot;
        }
        $programDir = EnvLoader::get($env, 'APP_PROGRAM_DIR');
        if ($programDir !== null) {
            $config['programDir'] = $programDir;
        }
        return $config;
    }

    /**
     * Overlay settings from the database (system_setting table).
     * Allows runtime configuration via the admin UI without redeploys.
     * DB values take precedence over `.env` and config.json.
     *
     * Keys are dotted (`auth0.domain`, `firebase.project_id`, …) and we
     * fold them into a nested array so callers can read
     * `$config['auth0']['domain']`. Secret-typed rows are stored
     * encrypted in the DB; here we read the raw bytes — callers that
     * need plaintext should go through {@see PdoSystemSettingService}.
     * The set of prefixes recognised is intentionally bounded so an
     * accidentally-misnamed row cannot silently replace a nested
     * structural key in config.json.
     */
    private static function overlayDb(array $config): array
    {
        try {
            if (
                empty($config['database']['dsn']) ||
                !isset($config['database']['user']) ||
                !isset($config['database']['password'])
            ) {
                return $config;
            }
            $pdo = new \PDO(
                $config['database']['dsn'],
                $config['database']['user'],
                $config['database']['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $allowedPrefixes = ['auth0', 'firebase', 'mail', 'ai', 'webhook'];
            $placeholders = implode(',', array_fill(0, count($allowedPrefixes), '?'));
            $likes = array_map(static fn (string $p): string => $p . '.%', $allowedPrefixes);
            $sql = "SELECT `key`, `value`, `value_type`, `encrypted` FROM `system_setting` WHERE "
                 . implode(' OR ', array_fill(0, count($likes), "`key` LIKE ?"));
            $stmt = $pdo->prepare($sql);
            $stmt->execute($likes);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $key = (string) ($row['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                // Encrypted rows are skipped here — overlay sees them as
                // unset so plaintext callers (PdoSystemSettingService)
                // remain the single source of truth for decryption.
                if (((int) ($row['encrypted'] ?? 0)) === 1) {
                    continue;
                }
                $value = (string) ($row['value'] ?? '');
                $type  = (string) ($row['value_type'] ?? 'string');
                $config = self::setDotted($config, $key, self::castValue($value, $type));
            }
        } catch (\PDOException $e) {
            // Silently ignore if the database is not available (e.g.
            // during initial setup before migrations run).
        }
        return $config;
    }

    /**
     * Set a nested config value from a dotted key like `firebase.api_key`.
     */
    private static function setDotted(array $config, string $dotted, mixed $value): array
    {
        $parts = explode('.', $dotted);
        $cursor = &$config;
        $last = array_pop($parts);
        foreach ($parts as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
        $cursor[$last] = $value;
        return $config;
    }

    private static function castValue(string $raw, string $type): mixed
    {
        return match ($type) {
            'int'    => (int) $raw,
            'bool'   => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json'   => json_decode($raw, true),
            default  => $raw,
        };
    }
}
