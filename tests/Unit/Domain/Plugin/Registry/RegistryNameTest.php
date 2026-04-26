<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Plugin\Registry;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\Registry\RegistryName;

final class RegistryNameTest extends TestCase
{
    public function testStoresValidName(): void
    {
        $n = new RegistryName('acme:custom-llm');

        self::assertSame('acme:custom-llm', $n->toString());
    }

    public function testReservedDetectsAbsenceOfColon(): void
    {
        self::assertTrue((new RegistryName('openai'))->isReserved());
        self::assertTrue((new RegistryName('null'))->isReserved());
        self::assertFalse((new RegistryName('acme:custom'))->isReserved());
        self::assertFalse((new RegistryName('vendor:foo'))->isReserved());
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RegistryName('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RegistryName(str_repeat('a', RegistryName::MAX_LENGTH + 1));
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RegistryName('Acme:Custom');
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RegistryName('acme custom');
    }

    public function testRejectsSlashes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RegistryName('acme/custom');
    }

    public function testEquals(): void
    {
        self::assertTrue((new RegistryName('a:b'))->equals(new RegistryName('a:b')));
        self::assertFalse((new RegistryName('a:b'))->equals(new RegistryName('a:c')));
    }
}
