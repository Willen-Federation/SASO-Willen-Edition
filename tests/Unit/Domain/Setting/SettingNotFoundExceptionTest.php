<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Setting;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Setting\Exception\SettingNotFoundException;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Shared\ErrorCode;

final class SettingNotFoundExceptionTest extends TestCase
{
    public function testCarriesConfigSettingNotFoundCode(): void
    {
        $ex = SettingNotFoundException::for(new SettingKey('default_locale'));

        self::assertSame(ErrorCode::ConfigSettingNotFound, $ex->errorCode());
        self::assertSame(404, $ex->errorCode()->httpStatus());
    }

    public function testCarriesKeyInContext(): void
    {
        $ex = SettingNotFoundException::for(new SettingKey('default_locale'));

        self::assertSame('default_locale', $ex->context()['key']);
    }

    public function testMessageIncludesKey(): void
    {
        $ex = SettingNotFoundException::for(new SettingKey('mail.smtp_host'));

        self::assertStringContainsString('mail.smtp_host', $ex->getMessage());
    }
}
