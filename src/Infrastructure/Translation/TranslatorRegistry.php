<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Translation;

use RuntimeException;

/**
 * Process-wide accessor for the application's {@see Translator}.
 *
 * The global `__()` helper (registered via Composer's `files` autoload)
 * delegates here so call sites in templates and legacy code can resolve
 * keys without manually wiring the translator through every layer. New
 * code in `src/` should inject {@see Translator} explicitly; the registry
 * exists for the boundary surfaces (templates, error handlers configured
 * before the request pipeline runs) where DI is impractical.
 *
 * Tests reset the registry between cases via {@see reset}.
 */
final class TranslatorRegistry
{
    private static ?Translator $instance = null;

    public static function set(Translator $translator): void
    {
        self::$instance = $translator;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function get(): Translator
    {
        if (self::$instance === null) {
            throw new RuntimeException(
                'Translator not initialised. Call TranslatorRegistry::set() before using __().',
            );
        }

        return self::$instance;
    }

    public static function isInitialised(): bool
    {
        return self::$instance !== null;
    }

    /**
     * @param array<string, scalar|null> $params
     */
    public static function trans(
        string $key,
        array $params = [],
        ?string $locale = null,
        ?string $fallback = null,
    ): string {
        return self::get()->trans($key, $params, $locale, $fallback);
    }
}
