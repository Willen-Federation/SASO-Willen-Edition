<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;

final class AuthProviderIdTest extends TestCase
{
    public function testStoresPositiveValue(): void
    {
        $id = new AuthProviderId(42);

        self::assertSame(42, $id->value);
        self::assertSame('42', $id->asString());
    }

    public function testRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuthProviderId(0);
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuthProviderId(-1);
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue((new AuthProviderId(7))->equals(new AuthProviderId(7)));
        self::assertFalse((new AuthProviderId(7))->equals(new AuthProviderId(8)));
    }
}
