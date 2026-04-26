<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Translation;

use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslator;

/**
 * Thin adapter over Symfony's translator that lets callers pass an
 * explicit fallback string for missing keys.
 *
 * Symfony's contract returns the key itself when no translation exists,
 * which is a sensible default for templates but unhelpful in HTTP error
 * paths where we already have a developer-facing English string in
 * `DomainException::$message`. The `$fallback` parameter promotes that
 * string when the catalogue lookup misses, so a freshly added error code
 * does not surface its raw key to the client.
 *
 * The adapter is intentionally thin: anything richer (pluralisation,
 * domain selection, ICU) should be added when an actual feature needs it,
 * not preemptively.
 */
final class Translator
{
    public function __construct(
        private readonly SymfonyTranslator $translator,
    ) {
    }

    /**
     * @param array<string, scalar|null> $params placeholders for the message
     */
    public function trans(
        string $key,
        array $params = [],
        ?string $locale = null,
        ?string $fallback = null,
    ): string {
        $result = $this->translator->trans($key, $params, null, $locale);

        // Symfony returns the key itself when no translation is found in
        // either the requested locale or the fallback locale chain.
        if ($result === $key && $fallback !== null) {
            return $fallback;
        }

        return $result;
    }

    public function setLocale(string $locale): void
    {
        if (method_exists($this->translator, 'setLocale')) {
            $this->translator->setLocale($locale);
        }
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }
}
