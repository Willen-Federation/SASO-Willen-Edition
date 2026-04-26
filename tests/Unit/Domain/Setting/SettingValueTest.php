<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Setting;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Setting\SettingType;
use Saso\Domain\Setting\SettingValue;

final class SettingValueTest extends TestCase
{
    public function testStringFactory(): void
    {
        $v = SettingValue::string('hello');

        self::assertSame(SettingType::String, $v->type);
        self::assertSame('hello', $v->asString());
    }

    public function testIntFactoryRoundTrips(): void
    {
        $v = SettingValue::int(42);

        self::assertSame(SettingType::Int, $v->type);
        self::assertSame(42, $v->asInt());
        self::assertSame('42', $v->asString());
    }

    public function testIntFactoryAcceptsNegative(): void
    {
        $v = SettingValue::int(-7);

        self::assertSame(-7, $v->asInt());
    }

    public function testBoolFactoryEncodesAsZeroOne(): void
    {
        self::assertSame('1', SettingValue::bool(true)->raw);
        self::assertSame('0', SettingValue::bool(false)->raw);
    }

    public function testBoolAsBool(): void
    {
        self::assertTrue(SettingValue::bool(true)->asBool());
        self::assertFalse(SettingValue::bool(false)->asBool());
    }

    public function testJsonFactoryRoundTrips(): void
    {
        $v = SettingValue::json(['enabled' => true, 'modes' => ['oidc', 'saml']]);

        self::assertSame(SettingType::Json, $v->type);
        self::assertSame(['enabled' => true, 'modes' => ['oidc', 'saml']], $v->asJson());
    }

    public function testJsonFactoryRejectsNonEncodable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // A resource is not JSON-encodable.
        SettingValue::json(['fp' => fopen('php://memory', 'r')]);
    }

    public function testSecretFactoryStoresPlaintext(): void
    {
        $v = SettingValue::secret('topsecret');

        self::assertSame(SettingType::Secret, $v->type);
        self::assertSame('topsecret', $v->asString());
    }

    public function testRawConstructorRejectsBadBoolFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingValue('yes', SettingType::Bool);
    }

    public function testRawConstructorRejectsBadIntFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingValue('not-a-number', SettingType::Int);
    }

    public function testRawConstructorRejectsBadJsonFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettingValue('{not json', SettingType::Json);
    }
}
