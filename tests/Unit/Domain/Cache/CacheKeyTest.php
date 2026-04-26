<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Cache;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Cache\CacheKey;

final class CacheKeyTest extends TestCase
{
    public function testStoresValidKey(): void
    {
        $k = new CacheKey('feature_flag:42');

        self::assertSame('feature_flag:42', $k->toString());
    }

    public function testAllowsAlphaNumColonsDotsHyphensUnderscores(): void
    {
        $k = new CacheKey('saso.search:item.42-abc');

        self::assertSame('saso.search:item.42-abc', $k->value);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheKey('');
    }

    public function testRejectsTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheKey(str_repeat('a', CacheKey::MAX_LENGTH + 1));
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheKey('feature flag');
    }

    public function testRejectsSlashes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheKey('a/b');
    }

    public function testRejectsControlCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CacheKey("foo\nbar");
    }

    public function testFromPartsJoinsWithColon(): void
    {
        $k = CacheKey::fromParts('feature_flag', '42');

        self::assertSame('feature_flag:42', $k->toString());
    }

    public function testFromPartsTrimsWhitespaceAndStrayColons(): void
    {
        $k = CacheKey::fromParts(' feature_flag ', ':42:');

        self::assertSame('feature_flag:42', $k->toString());
    }

    public function testFromPartsRejectsEmptySegment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CacheKey::fromParts('feature_flag', '');
    }

    public function testFromPartsRejectsEmptyArgList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CacheKey::fromParts();
    }

    public function testEquals(): void
    {
        self::assertTrue((new CacheKey('a:1'))->equals(new CacheKey('a:1')));
        self::assertFalse((new CacheKey('a:1'))->equals(new CacheKey('a:2')));
    }
}
