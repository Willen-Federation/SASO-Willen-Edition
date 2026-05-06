<?php

namespace saso\util;

/**
 * Companion to {@see EnvLoader}: rewrites a single key in a `.env` file
 * preserving every other line. Used by the installer bootstrap path to
 * fill in an auto-generated APP_KEY when the user opens /installer/start
 * on a fresh deployment without ever needing to edit the file by hand.
 *
 * Intentionally minimal — no quoting, no escaping, no creation of `.env`
 * from `.env.example`. Callers are expected to ensure the file exists and
 * the value passed in is a safe single-line string. Generated secrets
 * (base64 / hex random output) satisfy that constraint.
 */
final class EnvWriter
{
    /**
     * Set $key to $value in the file at $path. Appends a new line if the key
     * is not already present. Returns true on a successful write.
     */
    public static function set(string $path, string $key, string $value): bool
    {
        if (!is_file($path) || !is_writable($path)) {
            return false;
        }
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            return false;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return false;
        }

        $line    = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            $updated = preg_replace($pattern, $line, $contents, 1);
        } else {
            $updated = rtrim($contents, "\n")."\n".$line."\n";
        }

        return file_put_contents($path, $updated, LOCK_EX) !== false;
    }
}
