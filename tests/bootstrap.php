<?php

declare(strict_types=1);

/*
 * Test-time bootstrap.
 *
 * Composer's autoloader resolves the PSR-4 prefixes registered in
 * composer.json (Saso\\ → src/, Saso\Tests\\ → tests/). The legacy lowercase
 * `saso\\` namespace used by the pre-M3 codebase is not PSR-4 — its file
 * layout is driven at runtime by ConfigLoader (`documentRoot` / `programDir` /
 * `phpExtension` / `domainDepth`). Tests do not boot the application, so the
 * regular ClassLoader::load($config) machinery is unavailable.
 *
 * Instead, register a minimal autoloader that maps `saso\<rest>` to
 * `<project_root>/<rest>.php` (`\\` → `/`). This matches the on-disk layout
 * directly and is sufficient for the legacy classes that current and future
 * tests touch (entities, util helpers, monads).
 */

require __DIR__.'/../vendor/autoload.php';

// Define the saso\ENV constant used by ConfigLoader during tests
eval('namespace saso { const ENV = null; }');

// Load .env file for integration tests (EnvLoader is required by ConfigLoader)
require __DIR__.'/../util/EnvLoader.php';
$projectRoot = dirname(__DIR__);
$env = \saso\util\EnvLoader::loadFile($projectRoot.'/.env');
foreach ($env as $key => $value) {
    if (!getenv($key)) {
        putenv("$key=$value");
    }
}

$projectRoot = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($projectRoot): void {
    if (strncmp($class, 'saso\\', 5) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, 5));
    $path = $projectRoot.'/'.$relative.'.php';
    if (is_file($path)) {
        require_once $path;
    }
});
