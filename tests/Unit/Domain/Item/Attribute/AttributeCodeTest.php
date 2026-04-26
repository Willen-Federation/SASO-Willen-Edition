<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Item\Attribute;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Item\Attribute\AttributeCode;

final class AttributeCodeTest extends TestCase
{
    public function testStoresValidCode(): void
    {
        self::assertSame('size', (new AttributeCode('size'))->toString());
        self::assertSame('weight.kg', (new AttributeCode('weight.kg'))->toString());
        self::assertSame('country_of_origin', (new AttributeCode('country_of_origin'))->toString());
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttributeCode('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttributeCode(str_repeat('a', AttributeCode::MAX_LENGTH + 1));
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttributeCode('Size');
    }

    public function testRejectsHyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttributeCode('country-of-origin');
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AttributeCode('country of origin');
    }

    public function testEquals(): void
    {
        self::assertTrue((new AttributeCode('size'))->equals(new AttributeCode('size')));
        self::assertFalse((new AttributeCode('size'))->equals(new AttributeCode('weight')));
    }
}
