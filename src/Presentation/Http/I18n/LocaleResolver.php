<?php

declare(strict_types=1);

namespace Saso\Presentation\Http\I18n;

/**
 * Picks the locale a request should render under.
 *
 * Resolution order — first match wins:
 *
 *   1. `?lang=` query parameter — explicit override, useful for sharing a
 *      link in a specific language.
 *   2. Authenticated member's `locale` preference — saved in their profile.
 *   3. `Accept-Language` header — primary subtag of the highest-quality
 *      candidate that is in the supported list (e.g. `ja-JP` matches
 *      `ja`).
 *   4. Configured default.
 *
 * Anything not in the supported list is silently ignored — we never
 * surface a half-translated catalogue to a client.
 */
final class LocaleResolver
{
    /**
     * @param list<string> $supportedLocales locales served from `translations/`
     */
    public function __construct(
        private readonly array $supportedLocales = ['en', 'ja'],
        private readonly string $defaultLocale = 'en',
    ) {
    }

    public function resolve(
        ?string $queryLang = null,
        ?string $memberLocale = null,
        ?string $acceptLanguage = null,
        ?string $cookieLocale = null,
    ): string {
        if ($queryLang !== null && $this->isSupported($queryLang)) {
            return $queryLang;
        }

        if ($memberLocale !== null && $this->isSupported($memberLocale)) {
            return $memberLocale;
        }

        // Cookie wins over Accept-Language because it represents an explicit
        // user choice (the language switcher writes it). Falls below member
        // preference so logged-in users still see their saved preference.
        if ($cookieLocale !== null && $this->isSupported($cookieLocale)) {
            return $cookieLocale;
        }

        if ($acceptLanguage !== null && $acceptLanguage !== '') {
            foreach ($this->parseAcceptLanguage($acceptLanguage) as $candidate) {
                if ($this->isSupported($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->defaultLocale;
    }

    private function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales, true);
    }

    /**
     * Extracts primary-subtag candidates from an Accept-Language header,
     * ordered by descending quality. Invalid entries are skipped.
     *
     * Example: `ja-JP,ja;q=0.9,en-US;q=0.8,en;q=0.7` →
     * `['ja', 'en']`.
     *
     * @return list<string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $items = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(';q=', $part, 2);
            $tag      = $segments[0];
            $q        = isset($segments[1]) ? (float) $segments[1] : 1.0;

            $primary = strtolower(explode('-', $tag, 2)[0]);
            if ($primary === '' || $primary === '*') {
                continue;
            }

            $items[] = ['lang' => $primary, 'q' => $q];
        }

        usort($items, static fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        $ordered = [];
        foreach ($items as $item) {
            if (!in_array($item['lang'], $ordered, true)) {
                $ordered[] = $item['lang'];
            }
        }

        return $ordered;
    }
}
