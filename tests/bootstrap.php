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
