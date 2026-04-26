<?php

declare(strict_types=1);

/*
 * Phinx configuration (cf. ADR 0007).
 *
 * The `production` environment reads the same DB credentials the application
 * uses — `.env` first, then real environment variables, then `config.json` as
 * a last resort. This mirrors the runtime precedence so a Phinx run cannot
 * end up writing to a different schema than the request pipeline reads.
 *
 * Migration files live under `migrations/<milestone>/`; new milestones get
 * a sibling directory rather than getting added to a flat tree.
 */

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/util/EnvLoader.php';

$envFile = __DIR__.'/.env';
$envBag  = is_file($envFile) ? \saso\util\EnvLoader::loadFile($envFile) : [];

$resolve = static function (string $name, ?string $default = null) use ($envBag): ?string {
    if (array_key_exists($name, $envBag)) {
        return $envBag[$name];
    }
    $value = getenv($name);

    return $value === false ? $default : $value;
};

$dsn = $resolve('DB_DSN');
[$dbName, $dbHost, $dbPort, $dbCharset] = (function (?string $dsn): array {
    if ($dsn === null) {
        return [null, '127.0.0.1', '3306', 'utf8mb4'];
    }
    parse_str(strtr(substr($dsn, strpos($dsn, ':') + 1), ';', '&'), $parts);

    return [
        $parts['dbname']  ?? null,
        $parts['host']    ?? '127.0.0.1',
        $parts['port']    ?? '3306',
        $parts['charset'] ?? 'utf8mb4',
    ];
})($dsn);

return [
    'paths' => [
        'migrations' => [
            __DIR__.'/migrations/M1',
            __DIR__.'/migrations/M4',
        ],
        'seeds' => [
            __DIR__.'/seeds',
        ],
    ],
    'environments' => [
        'default_migration_table' => 'phinx_log',
        'default_environment'     => 'production',
        'production'              => [
            'adapter' => 'mysql',
            'host'    => $dbHost,
            'name'    => $dbName ?? 'saso',
            'user'    => $resolve('DB_USER', 'saso'),
            'pass'    => $resolve('DB_PASSWORD', ''),
            'port'    => (int) $dbPort,
            'charset' => $dbCharset,
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host'    => $resolve('TEST_DB_HOST', '127.0.0.1'),
            'name'    => $resolve('TEST_DB_NAME', 'saso_test'),
            'user'    => $resolve('TEST_DB_USER', 'root'),
            'pass'    => $resolve('TEST_DB_PASSWORD', ''),
            'port'    => (int) $resolve('TEST_DB_PORT', '3306'),
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
