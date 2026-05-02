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
        if(empty(self::$configFile)) {
            self::$configFile = ENV===null?$relative.'config.json':$relative.'config_'.ENV.'.json';
        }
        $config = json_decode(file_get_contents(self::$configFile), true);
        $env = EnvLoader::loadFile($relative.'.env');
        $config = self::overlayEnv($config, $env);
        $config = self::overlayDb($config);
        return self::regularization($config);
    }

    public static function regularization(array $config): array
    {
        $config['documentRoot'] = '/'.trim($config['documentRoot'], '/').'/';
        $config['programDir'] = trim($config['programDir'], '/').'/';
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
        return $config;
    }

    /**
     * Overlay settings from the database (system_setting table).
     * This allows runtime configuration of providers like Auth0 via the UI.
     * DB values take precedence over config.json and .env values.
     */
    private static function overlayDb(array $config): array
    {
        try {
            if (
                !empty($config['database']['dsn']) &&
                isset($config['database']['user']) &&
                isset($config['database']['password'])
            ) {
                // Establish a temporary connection to fetch settings.
                $pdo = new \PDO(
                    $config['database']['dsn'],
                    $config['database']['user'],
                    $config['database']['password'],
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
                );

                // Fetch all Auth0 provider settings from the system_setting table.
                $stmt = $pdo->query("SELECT `key`, `value` FROM `system_setting` WHERE `key` LIKE 'auth0.%'");
                $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

                if (!empty($settings)) {
                    if (!isset($config['auth0']) || !is_array($config['auth0'])) {
                        $config['auth0'] = [];
                    }
                    // Merge DB settings, overwriting any existing values.
                    if (!empty($settings['auth0.domain']))       $config['auth0']['domain']       = $settings['auth0.domain'];
                    if (!empty($settings['auth0.clientId']))     $config['auth0']['clientId']     = $settings['auth0.clientId'];
                    if (!empty($settings['auth0.clientSecret'])) $config['auth0']['clientSecret'] = $settings['auth0.clientSecret'];
                }
            }
        } catch (\PDOException $e) {
            // Silently ignore if the database is not available (e.g., during initial setup).
            // In a production system with mature logging, this would log the error.
        }
        return $config;
    }
}
