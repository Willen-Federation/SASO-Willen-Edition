<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Translation;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Infrastructure\Translation\TranslatorFactory;
use Saso\Infrastructure\Translation\TranslatorRegistry;

final class TranslatorRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorRegistry::reset();
    }

    protected function tearDown(): void
    {
        TranslatorRegistry::reset();
    }

    public function testGetThrowsWhenUninitialised(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translator not initialised');

        TranslatorRegistry::get();
    }

    public function testIsInitialisedTracksRegistration(): void
    {
        self::assertFalse(TranslatorRegistry::isInitialised());

        TranslatorRegistry::set(TranslatorFactory::create());

        self::assertTrue(TranslatorRegistry::isInitialised());
    }

    public function testTransDelegatesToTheRegisteredTranslator(): void
    {
        TranslatorRegistry::set(TranslatorFactory::create());

        self::assertSame(
            'Invalid credentials',
            TranslatorRegistry::trans('error.SASO-AUTH-1001.title'),
        );
    }

    public function testGlobalHelperDelegatesToTheRegistry(): void
    {
        TranslatorRegistry::set(TranslatorFactory::create());

        self::assertSame(
            '認証情報が正しくありません',
            __('error.SASO-AUTH-1001.title', locale: 'ja'),
        );
    }

    public function testGlobalHelperFallbackPropagates(): void
    {
        TranslatorRegistry::set(TranslatorFactory::create());

        self::assertSame(
            'fallback',
            __('error.NON.EXISTENT', fallback: 'fallback'),
        );
    }
}
