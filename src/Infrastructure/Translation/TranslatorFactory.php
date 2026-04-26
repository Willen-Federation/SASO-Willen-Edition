<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Translation;

use RuntimeException;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator as SymfonyTranslator;

/**
 * Builds a {@see Translator} from `translations/<locale>.yaml` files.
 *
 * `translations/` is the only ingestion point — adding a language is one
 * file and a corresponding nav-translation entry in `mkdocs.yml`. There is
 * no auto-discovery of translation files in modules; everything ships
 * centrally so a translator can find every string in one tree.
 */
final class TranslatorFactory
{
    /**
     * @param list<string>|null $supportedLocales optional whitelist; if
     *                                            provided, only these
     *                                            locale files are loaded
     */
    public static function create(
        string $defaultLocale = 'en',
        string $fallbackLocale = 'en',
        ?string $translationsDir = null,
        ?array $supportedLocales = null,
    ): Translator {
        $dir = $translationsDir ?? self::defaultTranslationsDir();

        if (!is_dir($dir)) {
            throw new RuntimeException(sprintf(
                'Translations directory not found: %s',
                $dir,
            ));
        }

        $sym = new SymfonyTranslator($defaultLocale);
        $sym->addLoader('yaml', new YamlFileLoader());
        $sym->setFallbackLocales([$fallbackLocale]);

        $files = glob($dir.'/*.yaml');
        if ($files === false) {
            $files = [];
        }
        sort($files);

        foreach ($files as $file) {
            $locale = pathinfo($file, PATHINFO_FILENAME);
            if ($supportedLocales !== null && !in_array($locale, $supportedLocales, true)) {
                continue;
            }
            $sym->addResource('yaml', $file, $locale);
        }

        return new Translator($sym);
    }

    private static function defaultTranslationsDir(): string
    {
        return dirname(__DIR__, 3).'/translations';
    }
}
