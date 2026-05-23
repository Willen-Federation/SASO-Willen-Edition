#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * tools/repair-app-key.php — safe, idempotent repair tool for the secrets that
 * boot `/api/v1/*`. Generates fresh values when APP_KEY / JWT_SECRET /
 * WEBHOOK_SECRET are missing or fail validation, leaves them alone otherwise,
 * and prints a summary so the operator knows what changed.
 *
 * Usage:
 *   php tools/repair-app-key.php [--dry-run] [--force] [--key=KEY] [--help]
 *
 * Flags:
 *   --dry-run        Show what would change without writing.
 *   --force          Regenerate even when the existing value passes validation.
 *   --key=KEY        Repair a single key only. One of:
 *                      app_key | jwt_secret | webhook_secret | all
 *                    Default: all.
 *   --env-path=PATH  Override the .env path. Default: <repo>/.env. Mainly for
 *                    tests; production should use the default.
 *   --help           Print this usage block.
 *
 * Exit codes:
 *   0 — all targeted keys are valid (after any write).
 *   1 — a write failed, or self-verification still rejected the value.
 *   2 — invalid CLI flags.
 *
 * See docs/runbooks/repair-app-key.md for production deployment notes.
 */

// ── Autoloader / dependency wiring ──────────────────────────────────────────
// This script is intentionally usable as `php tools/repair-app-key.php` from
// the repo root *without* requiring `composer install` to have updated any
// autoload entry. It bootstraps the same way `tests/bootstrap.php` does for
// the legacy `saso\` namespace.

$projectRoot = dirname(__DIR__);
require_once $projectRoot.'/vendor/autoload.php';
require_once $projectRoot.'/util/EnvLoader.php';
require_once $projectRoot.'/util/EnvWriter.php';

spl_autoload_register(static function (string $class) use ($projectRoot): void {
    if (strncmp($class, 'saso\\', 5) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, 5));
    $path     = $projectRoot.'/'.$relative.'.php';
    if (is_file($path)) {
        require_once $path;
    }
});

use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use saso\util\EnvWriter;

// ── CLI parsing ─────────────────────────────────────────────────────────────

/**
 * @param list<string> $argv
 *
 * @return array{dryRun:bool,force:bool,key:string,envPath:?string}
 */
function repairAppKey_parseArgs(array $argv): array
{
    $opts = ['dryRun' => false, 'force' => false, 'key' => 'all', 'envPath' => null];
    array_shift($argv); // strip script name
    foreach ($argv as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            repairAppKey_printUsage();
            exit(0);
        }
        if ($arg === '--dry-run') {
            $opts['dryRun'] = true;
            continue;
        }
        if ($arg === '--force') {
            $opts['force'] = true;
            continue;
        }
        if (str_starts_with($arg, '--key=')) {
            $value = substr($arg, 6);
            if (!in_array($value, ['app_key', 'jwt_secret', 'webhook_secret', 'all'], true)) {
                fwrite(STDERR, "Invalid --key value: $value\n");
                repairAppKey_printUsage();
                exit(2);
            }
            $opts['key'] = $value;
            continue;
        }
        if (str_starts_with($arg, '--env-path=')) {
            $opts['envPath'] = substr($arg, 11);
            continue;
        }
        fwrite(STDERR, "Unknown flag: $arg\n");
        repairAppKey_printUsage();
        exit(2);
    }
    return $opts;
}

function repairAppKey_printUsage(): void
{
    $usage = <<<USAGE
Usage: php tools/repair-app-key.php [OPTIONS]

  --dry-run        Show what would change without writing.
  --force          Regenerate even when the existing value is valid.
  --key=KEY        One of: app_key, jwt_secret, webhook_secret, all (default).
  --env-path=PATH  Override .env path (mainly for tests).
  --help           Show this message.

Exit codes: 0 = success, 1 = write/validation failure, 2 = bad flags.

USAGE;
    fwrite(STDOUT, $usage);
}

// ── Validators ──────────────────────────────────────────────────────────────

/**
 * Mirrors {@see AppKeyResolver::tryResolve()}: accepts base64-32B, hex-32B,
 * or any string ≥ 32 chars. Returning bool keeps the EnvWriter callback
 * contract simple.
 */
function repairAppKey_validateAppKey(string $value): bool
{
    if ($value === '') {
        return false;
    }
    $raw = base64_decode($value, true);
    if ($raw !== false && strlen($raw) === 32) {
        return true;
    }
    if (preg_match('/^[0-9a-fA-F]{64}$/', $value) === 1) {
        return true;
    }
    return strlen($value) >= 32;
}

/**
 * WEBHOOK_SECRET is sent as `X-Webhook-Token`. The codebase only requires
 * "non-empty"; we tighten to ≥ 32 chars to match the .env.example guidance
 * (`openssl rand -hex 32`).
 */
function repairAppKey_validateWebhookSecret(string $value): bool
{
    return $value !== '' && strlen($value) >= 32;
}

// ── Output helpers ──────────────────────────────────────────────────────────

function repairAppKey_isTty(): bool
{
    return function_exists('posix_isatty') && @posix_isatty(STDOUT);
}

function repairAppKey_paint(string $tag, string $colour): string
{
    if (!repairAppKey_isTty()) {
        return "[$tag]";
    }
    return "\033[".$colour.'m['.$tag."]\033[0m";
}

function repairAppKey_ok(string $msg): void
{
    fwrite(STDOUT, repairAppKey_paint('OK', '32').' '.$msg."\n");
}

function repairAppKey_warn(string $msg): void
{
    fwrite(STDOUT, repairAppKey_paint('WARN', '33').' '.$msg."\n");
}

function repairAppKey_err(string $msg): void
{
    fwrite(STDERR, repairAppKey_paint('ERR', '31').' '.$msg."\n");
}

function repairAppKey_info(string $msg): void
{
    fwrite(STDOUT, '       '.$msg."\n");
}

// ── Self-verification ───────────────────────────────────────────────────────

/**
 * Sets the env var as if the request was booting, then re-runs
 * AppKeyResolver::tryResolve()/JWT secret logic to confirm the value
 * is accepted by Bootstrap. Returns "base64-32B" / "hex-32B" / "passphrase"
 * or null if the value is still invalid.
 */
function repairAppKey_describeShape(string $value): ?string
{
    if ($value === '') {
        return null;
    }
    $raw = base64_decode($value, true);
    if ($raw !== false && strlen($raw) === 32) {
        return 'base64-32B';
    }
    if (preg_match('/^[0-9a-fA-F]{64}$/', $value) === 1) {
        return 'hex-32B';
    }
    if (strlen($value) >= 32) {
        return 'passphrase';
    }
    return null;
}

// ── Main loop ───────────────────────────────────────────────────────────────

/**
 * Description of one key being repaired.
 *
 * @return list<array{key:string,label:string,validator:callable(string):bool,bootValidator:?callable(string):bool}>
 */
function repairAppKey_targets(string $selector): array
{
    $all = [
        'app_key'        => [
            'key'           => 'APP_KEY',
            'label'         => 'APP_KEY',
            'validator'     => 'repairAppKey_validateAppKey',
            'bootValidator' => function (string $v): bool {
                // Simulate the Bootstrap path: set the env var, ask
                // AppKeyResolver, restore the prior env var.
                $prev = getenv('APP_KEY');
                putenv('APP_KEY='.$v);
                try {
                    return AppKeyResolver::tryResolve() !== null;
                } finally {
                    if ($prev === false) {
                        putenv('APP_KEY');
                    } else {
                        putenv('APP_KEY='.$prev);
                    }
                }
            },
        ],
        'jwt_secret'     => [
            'key'           => 'JWT_SECRET',
            'label'         => 'JWT_SECRET',
            'validator'     => 'repairAppKey_validateAppKey',
            'bootValidator' => static fn (string $v): bool => strlen($v) >= 32,
        ],
        'webhook_secret' => [
            'key'           => 'WEBHOOK_SECRET',
            'label'         => 'WEBHOOK_SECRET',
            'validator'     => 'repairAppKey_validateWebhookSecret',
            'bootValidator' => null,
        ],
    ];
    if ($selector === 'all') {
        return array_values($all);
    }
    return [$all[$selector]];
}

/**
 * @param array{dryRun:bool,force:bool} $opts
 * @param list<array{key:string,label:string,validator:callable(string):bool,bootValidator:?callable(string):bool}> $targets
 */
function repairAppKey_run(array $opts, string $envPath, array $targets): int
{
    $writer  = new EnvWriter();
    $summary = []; // key => 'changed' | 'preserved' | 'failed'

    $backupCreated = false;
    foreach ($targets as $target) {
        $envKey   = $target['key'];
        $validate = $target['validator'];

        $current = $writer->get($envKey, $envPath);
        $valid   = $current !== null && $current !== '' && $validate($current);

        if ($valid && !$opts['force']) {
            // $current is non-null here because $valid implies the !== null guard above.
            $shape = repairAppKey_describeShape((string) $current);
            repairAppKey_ok(sprintf('%s already valid (%s) — preserved.', $envKey, $shape ?? 'unknown'));
            $summary[$envKey] = 'preserved';
            continue;
        }

        $reason = !$valid ? 'invalid or missing' : 'forced regeneration';
        repairAppKey_warn(sprintf('%s needs repair (%s).', $envKey, $reason));

        $newValue = base64_encode(random_bytes(32));
        repairAppKey_info('Generated: base64-32B ('.strlen($newValue).' chars).');

        if ($opts['dryRun']) {
            repairAppKey_info('Dry-run: would write new value to '.$envPath);
            $summary[$envKey] = 'would-change';
            continue;
        }

        // Backup once per run on the first actual mutation, if file is non-empty.
        if (!$backupCreated && is_file($envPath) && filesize($envPath) > 0) {
            $backupPath = $envPath.'.backup.'.date('Ymd-His');
            if (@copy($envPath, $backupPath)) {
                @chmod($backupPath, 0600);
                repairAppKey_info('Backup: '.$backupPath);
                $backupCreated = true;
            } else {
                repairAppKey_warn('Could not create backup at '.$backupPath.' — continuing.');
            }
        }

        try {
            $writer->setOrUpdate($envKey, $newValue, $envPath);
        } catch (Throwable $e) {
            repairAppKey_err(sprintf('Failed to write %s: %s', $envKey, $e->getMessage()));
            $summary[$envKey] = 'failed';
            continue;
        }

        // Self-verify post-write.
        $written = $writer->get($envKey, $envPath);
        if ($written === null || !$validate($written)) {
            repairAppKey_err(sprintf('Post-write validation failed for %s.', $envKey));
            $summary[$envKey] = 'failed';
            continue;
        }
        $bootValidator = $target['bootValidator'];
        if (is_callable($bootValidator) && !$bootValidator($written)) {
            repairAppKey_err(sprintf('Boot-time validation rejected new %s.', $envKey));
            $summary[$envKey] = 'failed';
            continue;
        }
        $shape = repairAppKey_describeShape($written);
        repairAppKey_ok(sprintf('%s validated: %s', $envKey, $shape ?? 'unknown'));
        $summary[$envKey] = 'changed';
    }

    repairAppKey_printSummary($summary, $opts['dryRun']);

    foreach ($summary as $status) {
        if ($status === 'failed') {
            return 1;
        }
    }
    return 0;
}

/**
 * @param array<string,string> $summary
 */
function repairAppKey_printSummary(array $summary, bool $dryRun): void
{
    fwrite(STDOUT, "\nSummary".($dryRun ? ' (dry-run)' : '').":\n");
    foreach ($summary as $key => $status) {
        $label = match ($status) {
            'changed'      => 'changed',
            'preserved'    => 'preserved',
            'would-change' => 'would change',
            'failed'       => 'FAILED',
            default        => $status,
        };
        fwrite(STDOUT, sprintf("  %-16s %s\n", $key, $label));
    }
}

// ── Entry point (skipped when included for testing) ─────────────────────────

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    /** @var list<string> $argv */
    $argv = $_SERVER['argv'] ?? [];
    /** @var array{dryRun:bool,force:bool,key:string,envPath:?string} $opts */
    $opts = repairAppKey_parseArgs($argv);

    $envPath = $opts['envPath'] ?? ($projectRoot.'/.env');
    $targets = repairAppKey_targets($opts['key']);

    exit(repairAppKey_run(['dryRun' => $opts['dryRun'], 'force' => $opts['force']], $envPath, $targets));
}
