<?php

declare(strict_types=1);

/*
 * i18n smoke test.
 *
 * Walks every key referenced via `__('...')` in repository templates,
 * resolves it under each supported locale, and reports keys whose
 * resolution returns the key itself (Symfony's "missing key" sentinel).
 *
 * Exits non-zero when at least one key is missing in any locale.
 *
 * Usage:  php tests/i18n/missing-keys.php
 *         (also wired as `make i18n-check`)
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$root = dirname(__DIR__, 2);

$translator = \Saso\Infrastructure\Translation\TranslatorFactory::create();
\Saso\Infrastructure\Translation\TranslatorRegistry::set($translator);

$locales = ['en', 'ja'];

// Discover every distinct first arg passed to `__('...')` across PHP files.
$keys = [];
$roots = ['root/template', 'auth', 'item', 'label', 'shelf', 'category', 'feature', 'common', 'start', 'archive', 'installer', 'verify', 'authExt', 'featureAdmin'];

foreach ($roots as $sub) {
    $absSub = $root . '/' . $sub;
    if (!is_dir($absSub)) {
        continue;
    }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absSub));
    foreach ($rii as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['php', 'phtml'], true)) {
            continue;
        }
        $contents = (string) file_get_contents($file->getPathname());
        if (preg_match_all("/__\\(\\s*'([^'\\\\]+)'/", $contents, $m)) {
            foreach ($m[1] as $k) {
                $keys[$k] = true;
            }
        }
        if (preg_match_all('/__\\(\\s*"([^"\\\\]+)"/', $contents, $m)) {
            foreach ($m[1] as $k) {
                $keys[$k] = true;
            }
        }
    }
}

$keys = array_keys($keys);
sort($keys);

$missing = [];
foreach ($locales as $lc) {
    foreach ($keys as $k) {
        $resolved = $translator->trans($k, [], $lc);
        if ($resolved === $k) {
            $missing[$lc][] = $k;
        }
    }
}

if (empty($missing)) {
    echo sprintf("OK — %d keys resolved in all locales (%s)\n", count($keys), implode(', ', $locales));
    exit(0);
}

foreach ($missing as $lc => $list) {
    fwrite(STDERR, "Missing in {$lc} (" . count($list) . "):\n");
    foreach ($list as $k) {
        fwrite(STDERR, "  - {$k}\n");
    }
}
exit(1);
