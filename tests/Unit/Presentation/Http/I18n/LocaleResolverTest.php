<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Http\I18n;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Http\I18n\LocaleResolver;

final class LocaleResolverTest extends TestCase
{
    public function testQueryLangWinsOverEverythingElse(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(
                queryLang: 'ja',
                memberLocale: 'en',
                acceptLanguage: 'en-US,en;q=0.9',
            ),
        );
    }

    public function testQueryLangIgnoredIfUnsupported(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'en',
            $resolver->resolve(
                queryLang: 'fr',
                memberLocale: 'en',
            ),
        );
    }

    public function testMemberLocaleWinsOverAcceptLanguage(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'en',
            $resolver->resolve(
                memberLocale: 'en',
                acceptLanguage: 'ja-JP,ja;q=0.9',
            ),
        );
    }

    public function testMemberLocaleIgnoredIfUnsupported(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(
                memberLocale: 'fr',
                acceptLanguage: 'ja-JP,ja;q=0.9',
            ),
        );
    }

    public function testCookieLocaleWinsOverAcceptLanguage(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(
                acceptLanguage: 'en-US,en;q=0.9',
                cookieLocale: 'ja',
            ),
        );
    }

    public function testMemberLocaleWinsOverCookieLocale(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'en',
            $resolver->resolve(
                memberLocale: 'en',
                cookieLocale: 'ja',
            ),
        );
    }

    public function testCookieLocaleIgnoredIfUnsupported(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(
                acceptLanguage: 'ja-JP',
                cookieLocale: 'fr',
            ),
        );
    }

    public function testAcceptLanguageHighestQualitySupportedSubtagWins(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(
                acceptLanguage: 'fr-FR;q=1.0,ja;q=0.9,en;q=0.5',
            ),
        );
    }

    public function testAcceptLanguagePrimarySubtagExtraction(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(acceptLanguage: 'ja-JP'),
        );
    }

    public function testWildcardAcceptLanguageIsIgnored(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'en',
            $resolver->resolve(acceptLanguage: '*'),
        );
    }

    public function testDefaultLocaleWhenEverythingFails(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame('en', $resolver->resolve());
    }

    public function testCustomDefaultIsHonoured(): void
    {
        $resolver = new LocaleResolver(supportedLocales: ['en', 'ja'], defaultLocale: 'ja');

        self::assertSame('ja', $resolver->resolve());
    }

    public function testCustomSupportedListExpandsCandidates(): void
    {
        $resolver = new LocaleResolver(supportedLocales: ['en', 'ja', 'fr'], defaultLocale: 'en');

        self::assertSame(
            'fr',
            $resolver->resolve(queryLang: 'fr'),
        );
    }

    public function testEmptyAcceptLanguageDoesNotErrorOut(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame('en', $resolver->resolve(acceptLanguage: ''));
    }

    public function testAcceptLanguageWithoutQuality(): void
    {
        $resolver = new LocaleResolver();

        self::assertSame(
            'ja',
            $resolver->resolve(acceptLanguage: 'ja'),
        );
    }
}
