<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\util\CSRFtoken;

#[CoversClass(CSRFtoken::class)]
final class CsrftokenTest extends TestCase
{
    protected function setUp(): void
    {
        // Each test starts with a clean session-state proxy. PHPUnit runs
        // without a real session; CSRFtoken uses $_SESSION as a plain array,
        // which is the correct behavior for tests.
        $_SESSION = [];
    }

    public function testCurrentGeneratesA64HexCharToken(): void
    {
        $token = CSRFtoken::current();
        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testCurrentReturnsTheSameTokenWithinASession(): void
    {
        $first = CSRFtoken::current();
        $second = CSRFtoken::current();
        self::assertSame($first, $second);
    }

    public function testRotateForcesANewToken(): void
    {
        $first = CSRFtoken::current();
        CSRFtoken::rotate();
        $second = CSRFtoken::current();
        self::assertNotSame($first, $second);
    }

    public function testVerifyAcceptsCurrentToken(): void
    {
        $token = CSRFtoken::current();
        self::assertTrue(CSRFtoken::verify($token));
    }

    public function testVerifyRejectsTamperedToken(): void
    {
        $token = CSRFtoken::current();
        $tampered = $token === str_repeat('a', 64) ? str_repeat('b', 64) : str_repeat('a', 64);
        self::assertFalse(CSRFtoken::verify($tampered));
    }

    public function testVerifyRejectsEmptyStringWhenNoTokenIssued(): void
    {
        self::assertFalse(CSRFtoken::verify(''));
    }

    public function testVerifyRejectsBeforeRotation(): void
    {
        $oldToken = CSRFtoken::current();
        CSRFtoken::rotate();
        self::assertFalse(CSRFtoken::verify($oldToken));
    }

    public function testSaltingAliasReturnsCurrentTokenAndIgnoresArgument(): void
    {
        // Backward-compat alias retained through M2 — must keep returning the
        // current session token regardless of the salt argument so old config
        // wiring keeps working until the alias is removed.
        $expected = CSRFtoken::current();
        self::assertSame($expected, CSRFtoken::salting('any-salt-value'));
        self::assertSame($expected, CSRFtoken::salting(''));
    }
}
