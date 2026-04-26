<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\StorageLocation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\StorageLocation\LocationCode;

final class LocationCodeTest extends TestCase
{
    public function testStoresCanonicalCode(): void
    {
        $c = new LocationCode('WH1-A-03-B12');

        self::assertSame('WH1-A-03-B12', $c->toString());
    }

    public function testRejectsLowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode('wh1-a-03');
    }

    public function testRejectsTrailingHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode('WH1-A-');
    }

    public function testRejectsLeadingHyphen(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode('-WH1-A');
    }

    public function testRejectsConsecutiveHyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode('WH1--A');
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode('WH 1');
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LocationCode(str_repeat('A', LocationCode::MAX_LENGTH + 1));
    }

    public function testFromPartsUppercasesAndJoins(): void
    {
        $c = LocationCode::fromParts('wh1', 'a', '03', 'B12');

        self::assertSame('WH1-A-03-B12', $c->toString());
    }

    public function testFromPartsTrims(): void
    {
        $c = LocationCode::fromParts(' wh1 ', '  a');

        self::assertSame('WH1-A', $c->toString());
    }

    public function testFromPartsRejectsEmptySegment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LocationCode::fromParts('WH1', '');
    }

    public function testFromPartsRejectsEmptyArgList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        LocationCode::fromParts();
    }

    public function testEquals(): void
    {
        self::assertTrue((new LocationCode('A'))->equals(new LocationCode('A')));
        self::assertFalse((new LocationCode('A'))->equals(new LocationCode('B')));
    }
}
