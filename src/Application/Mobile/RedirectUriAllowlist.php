<?php

declare(strict_types=1);

namespace Saso\Application\Mobile;

/**
 * Allowlist for redirect_uri values accepted by the mobile setup flow.
 *
 * Patterns are exact-match strings, with one exception: a host of the form
 * `host:*` matches any TCP port on that host (used for `localhost:*` during
 * development so the Flutter web preview port doesn't have to be hard-coded).
 *
 * Default values:
 *   - jp.willen.saso://callback   (custom URL scheme registered by the iOS/Android app)
 *   - http://localhost:*
 *   - http://127.0.0.1:*
 *
 * Override via:
 *   - config['mobile']['redirect_uri_allowlist']  (array<string>)
 *   - MOBILE_REDIRECT_URI_ALLOWLIST env var       (comma-separated)
 *
 * Both sources concatenate, so an env var augments the config file rather
 * than replacing it. Empty entries are dropped.
 */
final class RedirectUriAllowlist
{
    /**
     * @param list<string> $patterns
     */
    public function __construct(private readonly array $patterns)
    {
    }

    public static function fromConfig(array $config): self
    {
        $cfg = $config['mobile']['redirect_uri_allowlist'] ?? null;
        if (!is_array($cfg)) {
            $file = dirname(__DIR__, 3).'/config/mobile.php';
            if (is_file($file)) {
                $loaded = require $file;
                $cfg    = is_array($loaded['redirect_uri_allowlist'] ?? null)
                    ? $loaded['redirect_uri_allowlist']
                    : [];
            } else {
                $cfg = [];
            }
        }

        $env = (string) (getenv('MOBILE_REDIRECT_URI_ALLOWLIST') ?: '');
        if ($env !== '') {
            foreach (explode(',', $env) as $entry) {
                $trimmed = trim($entry);
                if ($trimmed !== '') {
                    $cfg[] = $trimmed;
                }
            }
        }

        $patterns = array_values(array_unique(array_filter(
            array_map('strval', $cfg),
            fn ($s) => $s !== '',
        )));

        return new self($patterns);
    }

    public function isAllowed(string $uri): bool
    {
        if ($uri === '' || strlen($uri) > 2048) {
            return false;
        }
        foreach ($this->patterns as $p) {
            if ($this->matches($p, $uri)) {
                return true;
            }
        }
        return false;
    }

    private function matches(string $pattern, string $uri): bool
    {
        if ($pattern === $uri) {
            return true;
        }

        // Wildcard port form: scheme://host:* matches scheme://host:<digits>
        if (preg_match('#^([a-zA-Z][a-zA-Z0-9+.\-]*://[^/:]+):\*(/.*)?$#', $pattern, $m) === 1) {
            $prefix    = $m[1].':';
            $suffix    = $m[2] ?? '';
            $remainder = substr($uri, strlen($prefix));
            if ($remainder === false || $remainder === '') {
                return false;
            }
            $i = 0;
            while ($i < strlen($remainder) && ctype_digit($remainder[$i])) {
                $i++;
            }
            if ($i === 0) {
                return false;
            }
            $rest = substr($remainder, $i);
            if ($suffix === '') {
                return $rest === '' || $rest[0] === '/';
            }
            return $rest === $suffix;
        }

        return false;
    }

    /**
     * Exposed for unit tests.
     *
     * @return list<string>
     */
    public function patterns(): array
    {
        return $this->patterns;
    }
}
