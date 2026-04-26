<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Translation;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Infrastructure\Translation\TranslatorFactory;

final class TranslatorFactoryTest extends TestCase
{
    public function testThrowsWhenTranslationsDirectoryIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translations directory not found');

        TranslatorFactory::create(translationsDir: '/nonexistent/path/translations');
    }

    public function testLoadsTheRealProjectCatalogue(): void
    {
        $t = TranslatorFactory::create();

        // The bundled English catalogue must contain at least the M3-B
        // codes — guards against an empty / accidentally-committed YAML.
        self::assertSame(
            'Invalid credentials',
            $t->trans('error.SASO-AUTH-1001.title'),
        );
        self::assertSame(
            'CSRF token mismatch',
            $t->trans('error.SASO-AUTH-1003.title'),
        );
    }

    public function testJapaneseCatalogueShipsForEveryAuthCode(): void
    {
        $t = TranslatorFactory::create();

        $codes = [
            'SASO-AUTH-1001',
            'SASO-AUTH-1002',
            'SASO-AUTH-1003',
            'SASO-AUTH-1004',
            'SASO-AUTH-1005',
        ];

        foreach ($codes as $code) {
            $title = $t->trans("error.{$code}.title", locale: 'ja');
            self::assertNotSame(
                "error.{$code}.title",
                $title,
                "Japanese title missing for {$code}",
            );
        }
    }

    public function testSupportedLocalesWhitelistFiltersFiles(): void
    {
        $t = TranslatorFactory::create(supportedLocales: ['en']);

        // ja file is on disk but should be excluded — looking up a JA
        // string falls back to the English entry.
        self::assertSame(
            'Invalid credentials',
            $t->trans('error.SASO-AUTH-1001.title', locale: 'ja'),
        );
    }
}
