<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Setting;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Setting\SettingKey;

final class SettingKeyTest extends TestCase
{
    public function testStoresValidKey(): void
    {
        $k = new SettingKey('default_locale');

        self::assertSame('default_locale', $k->toString());
    }

    public function testAllowsAlphaNumDotsHyphensUnderscores(): void
    {
        $k = new SettingKey('mail.smtp_host-1');

        self::assertSame('mail.smtp_host-1', $k->value);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingKey('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingKey(str_repeat('a', SettingKey::MAX_LENGTH + 1));
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingKey('default locale');
    }

    public function testRejectsSlashes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingKey('mail/smtp');
    }

    public function testEquals(): void
    {
        self::assertTrue((new SettingKey('a'))->equals(new SettingKey('a')));
        self::assertFalse((new SettingKey('a'))->equals(new SettingKey('b')));
    }
}
