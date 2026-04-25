<?php
namespace saso\util;

/**
 * Minimal `.env` parser used to keep secrets out of config.json.
 *
 * Supports the common subset: KEY=value lines, `#` and blank-line comments,
 * and optional surrounding single or double quotes. Variable interpolation
 * (`${OTHER}`), command substitution, and multi-line values are intentionally
 * NOT supported — when M2 introduces Composer this class will be replaced
 * with vlucas/phpdotenv, which provides those features safely.
 *
 * The loader is deliberately framework-free so that it can be `require_once`-d
 * by ConfigLoader before the SPL autoloader is registered.
 */
final class EnvLoader
{
    /**
     * Parse the given file into an associative array. Returns [] when the
     * file is missing or unreadable so callers can treat .env as optional.
     */
    public static function loadFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }
        $env = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }
            $eq = strpos($trimmed, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($trimmed, 0, $eq));
            if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }
            $value = trim(substr($trimmed, $eq + 1));
            $env[$key] = self::stripQuotes($value);
        }
        return $env;
    }

    /**
     * Look up a key with precedence: explicit .env array > getenv() > default.
     * Returning null lets callers distinguish "unset" from the empty string.
     */
    public static function get(array $env, string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $env)) {
            return $env[$key];
        }
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }

    private static function stripQuotes(string $value): string
    {
        $len = strlen($value);
        if ($len < 2) {
            return $value;
        }
        $first = $value[0];
        $last = $value[$len - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }
        return $value;
    }
}
