<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Setting;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Setting\SettingType;

final class SettingTypeTest extends TestCase
{
    public function testEnumValuesMatchDatabaseEnum(): void
    {
        self::assertSame('string', SettingType::String->value);
        self::assertSame('int', SettingType::Int->value);
        self::assertSame('bool', SettingType::Bool->value);
        self::assertSame('json', SettingType::Json->value);
        self::assertSame('secret', SettingType::Secret->value);
    }

    public function testIsSecretFlag(): void
    {
        self::assertTrue(SettingType::Secret->isSecret());
        self::assertFalse(SettingType::String->isSecret());
        self::assertFalse(SettingType::Int->isSecret());
        self::assertFalse(SettingType::Bool->isSecret());
        self::assertFalse(SettingType::Json->isSecret());
    }

    public function testCovers(): void
    {
        self::assertCount(5, SettingType::cases());
    }
}
