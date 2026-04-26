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
        return TranslatorRegistry::trans($key, $params, $locale, $fallback);
    }
}
