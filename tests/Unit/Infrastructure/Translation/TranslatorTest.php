<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Translation;

use PHPUnit\Framework\TestCase;
use Saso\Infrastructure\Translation\TranslatorFactory;

final class TranslatorTest extends TestCase
{
    private const TRANSLATIONS_DIR = __DIR__.'/Fixtures';

    protected function setUp(): void
    {
        if (!is_dir(self::TRANSLATIONS_DIR)) {
            mkdir(self::TRANSLATIONS_DIR, 0o755, true);
        }
        file_put_contents(
            self::TRANSLATIONS_DIR.'/en.yaml',
            <<<'YAML'
                error:
                  SASO-AUTH-1001:
                    title: Invalid credentials
                    detail: 'Bad password for {user}.'
                YAML
        );
        file_put_contents(
            self::TRANSLATIONS_DIR.'/ja.yaml',
            <<<'YAML'
                error:
                  SASO-AUTH-1001:
                    title: 認証情報が正しくありません
                YAML
        );
    }

    protected function tearDown(): void
    {
        @unlink(self::TRANSLATIONS_DIR.'/en.yaml');
        @unlink(self::TRANSLATIONS_DIR.'/ja.yaml');
        @rmdir(self::TRANSLATIONS_DIR);
    }

    public function testResolvesEnglishKey(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        self::assertSame(
            'Invalid credentials',
            $t->trans('error.SASO-AUTH-1001.title'),
        );
    }

    public function testResolvesJapaneseKeyWhenLocaleIsJa(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        self::assertSame(
            '認証情報が正しくありません',
            $t->trans('error.SASO-AUTH-1001.title', locale: 'ja'),
        );
    }

    public function testFallsBackToEnglishWhenJapaneseTranslationIsMissing(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        // ja.yaml only ships `title` for SASO-AUTH-1001 — `detail` should
        // fall through to the en.yaml entry.
        self::assertSame(
            'Bad password for {user}.',
            $t->trans('error.SASO-AUTH-1001.detail', locale: 'ja'),
        );
    }

    public function testReturnsFallbackWhenKeyIsCompletelyMissing(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        self::assertSame(
            'fallback string',
            $t->trans('error.NON.EXISTENT', fallback: 'fallback string'),
        );
    }

    public function testReturnsKeyItselfWhenMissingAndNoFallbackProvided(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        self::assertSame(
            'error.NON.EXISTENT',
            $t->trans('error.NON.EXISTENT'),
        );
    }

    public function testInterpolatesParameters(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        self::assertSame(
            'Bad password for alice.',
            $t->trans(
                'error.SASO-AUTH-1001.detail',
                ['{user}' => 'alice'],
            ),
        );
    }

    public function testGetLocaleReturnsConfiguredDefault(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );

        self::assertSame('en', $t->getLocale());
    }

    public function testSetLocaleUpdatesDefault(): void
    {
        $t = TranslatorFactory::create(
            defaultLocale: 'en',
            translationsDir: self::TRANSLATIONS_DIR,
        );
        $t->setLocale('ja');

        self::assertSame('ja', $t->getLocale());
    }
}
