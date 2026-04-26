<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Feature;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureKey;

final class FeatureKeyTest extends TestCase
{
    public function testStoresValidKey(): void
    {
        $k = new FeatureKey('checkout.new_flow');

        self::assertSame('checkout.new_flow', $k->toString());
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FeatureKey('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FeatureKey(str_repeat('a', FeatureKey::MAX_LENGTH + 1));
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FeatureKey('Checkout.NewFlow');
    }

    public function testRejectsHyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FeatureKey('checkout-new-flow');
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FeatureKey('checkout new flow');
    }

    public function testEquals(): void
    {
        self::assertTrue((new FeatureKey('a.b'))->equals(new FeatureKey('a.b')));
        self::assertFalse((new FeatureKey('a.b'))->equals(new FeatureKey('a.c')));
    }
}
