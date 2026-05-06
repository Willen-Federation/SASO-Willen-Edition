<?php

declare(strict_types=1);

/*
 * Global `__()` helper.
 *
 * Auto-registered via the Composer `files` autoload. Delegates to
 * {@see \Saso\Infrastructure\Translation\TranslatorRegistry::trans},
 * which throws if no translator has been bound yet. Templates and the
 * legacy view layer (until it migrates into `src/Presentation/Web/`)
 * call this function directly; new code in `src/` should depend on
 * {@see \Saso\Infrastructure\Translation\Translator} via constructor
 * injection instead.
 */

use Saso\Infrastructure\Translation\TranslatorRegistry;

if (!function_exists('__')) {
    /**
     * @param array<string, scalar|null> $params placeholders for the message
     */
    function __(
        string $key,
        array $params = [],
        ?string $locale = null,
        ?string $fallback = null,
    ): string {
        // TranslatorRegistry::trans throws when no translator is bound (e.g.
        // production hasn't completed i18n bootstrap, schema mismatch, etc.).
        // A throwing __() turns every translation-using template into a
        // whiteout, so we degrade gracefully to the caller-supplied fallback
        // (or the bare key when none was provided) instead.
        try {
            return TranslatorRegistry::trans($key, $params, $locale, $fallback);
        } catch (\Throwable $e) {
            return $fallback ?? $key;
        }
    }
}
