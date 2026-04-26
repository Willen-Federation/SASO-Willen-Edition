<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util\Monad;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\util\monad\Either;
use saso\util\monad\Left;
use saso\util\monad\Right;

#[CoversClass(Either::class)]
#[CoversClass(Right::class)]
#[CoversClass(Left::class)]
final class EitherTest extends TestCase
{
    public function testOfWrapsValueInRight(): void
    {
        $either = Either::of(42);
        self::assertInstanceOf(Right::class, $either);
        self::assertSame(42, $either->getOrElse('fallback'));
    }

    public function testLeftWrapsValueInLeft(): void
    {
        $either = Either::left('boom');
        self::assertInstanceOf(Left::class, $either);
        self::assertSame('fallback', $either->getOrElse('fallback'));
    }

    public function testFromNullableYieldsRightForNonFalseValues(): void
    {
        // Despite the name, fromNullable() in this codebase rejects only `false`
        // — it was originally designed for filter_var() return values, where
        // `false` signals validation failure. `null` flows through as Right(null);
        // any rename of this method would be a behavior change.
        $either = Either::fromNullable('hello');
        self::assertInstanceOf(Right::class, $either);
        self::assertSame('hello', $either->getOrElse(null));
    }

    public function testFromNullableYieldsLeftForFalse(): void
    {
        $either = Either::fromNullable(false);
        self::assertInstanceOf(Left::class, $either);
    }

    public function testFromNullableYieldsRightForNull(): void
    {
        // Documented quirk: null is not the "nullable" sentinel here. It is
        // wrapped as Right(null) and downstream callers must use filter() if
        // they want to reject it.
        $either = Either::fromNullable(null);
        self::assertInstanceOf(Right::class, $either);
    }

    public function testRightMapAppliesFunction(): void
    {
        $result = Either::of(10)->map(static fn ($v) => $v * 2);
        self::assertInstanceOf(Right::class, $result);
        self::assertSame(20, $result->getOrElse(0));
    }

    public function testLeftMapShortCircuits(): void
    {
        $callCount = 0;
        $result = Either::left('err')->map(static function ($v) use (&$callCount) {
            unset($v);
            $callCount++;
            return null;
        });
        self::assertInstanceOf(Left::class, $result);
        self::assertSame(0, $callCount);
    }

    public function testRightFlatMapChains(): void
    {
        $result = Either::of(3)->flatMap(static fn ($v) => Either::of($v + 1));
        self::assertInstanceOf(Right::class, $result);
        self::assertSame(4, $result->getOrElse(null));
    }

    public function testRightFlatMapToLeftPropagatesLeft(): void
    {
        $result = Either::of(3)->flatMap(static function ($v) {
            unset($v);
            return Either::left('downstream-err');
        });
        self::assertInstanceOf(Left::class, $result);
    }

    public function testLeftFlatMapShortCircuits(): void
    {
        $callCount = 0;
        $result = Either::left('err')->flatMap(static function ($v) use (&$callCount) {
            unset($v);
            $callCount++;
            return Either::of(null);
        });
        self::assertInstanceOf(Left::class, $result);
        self::assertSame(0, $callCount);
    }

    public function testFilterKeepsRightWhenPredicatePasses(): void
    {
        $result = Either::of(10)->filter(static fn ($v) => $v > 5);
        self::assertInstanceOf(Right::class, $result);
        self::assertSame(10, $result->getOrElse(null));
    }

    public function testFilterTurnsRightIntoLeftWhenPredicateFails(): void
    {
        $result = Either::of(2)->filter(static fn ($v) => $v > 5);
        self::assertInstanceOf(Left::class, $result);
    }

    public function testGetOrElseThrowReturnsRightValue(): void
    {
        $value = Either::of('ok')->getOrElseThrow('not-thrown');
        self::assertSame('ok', $value);
    }

    public function testGetOrElseThrowThrowsForLeft(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('boom');
        Either::left('whatever')->getOrElseThrow('boom');
    }

    public function testOrElseInvokesCallbackOnLeft(): void
    {
        $captured = null;
        $result = Either::left('the-error')->orElse(static function ($v) use (&$captured) {
            $captured = $v;
            return Either::of('recovered');
        });
        self::assertSame('the-error', $captured);
        self::assertSame('recovered', $result->getOrElse(null));
    }

    public function testOrElseSkipsCallbackOnRight(): void
    {
        $callCount = 0;
        $result = Either::of('value')->orElse(static function ($v) use (&$callCount) {
            unset($v);
            $callCount++;
            return Either::of('replacement');
        });
        // The legacy Right::orElse implementation returns $this without invoking
        // the closure, which is the correct early-return semantics.
        self::assertSame(0, $callCount);
        self::assertSame('value', $result->getOrElse(null));
    }
}
