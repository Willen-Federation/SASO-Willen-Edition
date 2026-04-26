<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\ClaimMapping;

final class ClaimMappingTest extends TestCase
{
    public function testDefaultMapMatchesOidcStandardClaims(): void
    {
        $m = new ClaimMapping();

        $claims = [
            'sub'    => 'auth0|user-1',
            'email'  => 'alice@example.com',
            'name'   => 'Alice',
            'groups' => ['admin', 'staff'],
        ];

        self::assertSame('auth0|user-1', $m->extract('subject', $claims));
        self::assertSame('alice@example.com', $m->extract('email', $claims));
        self::assertSame('Alice', $m->extract('display_name', $claims));
        self::assertSame(['admin', 'staff'], $m->extract('roles', $claims));
    }

    public function testWithOverridesReplacesSpecifiedFields(): void
    {
        $m = ClaimMapping::withOverrides([
            'display_name' => 'preferred_username',
            'roles'        => 'cognito:groups',
        ]);

        $claims = [
            'sub'                 => 's',
            'email'               => 'x@y',
            'preferred_username'  => 'alice42',
            'cognito:groups'      => ['admin'],
        ];

        self::assertSame('alice42', $m->extract('display_name', $claims));
        self::assertSame(['admin'], $m->extract('roles', $claims));
    }

    public function testReturnsNullForUnknownField(): void
    {
        $m = new ClaimMapping();

        self::assertNull($m->extract('unknown_field', ['sub' => 's']));
    }

    public function testReturnsNullWhenMappedClaimMissing(): void
    {
        $m = new ClaimMapping();

        self::assertNull($m->extract('email', ['sub' => 's']));
    }

    public function testExtractStringReturnsStringValuesOnly(): void
    {
        $m = new ClaimMapping();

        self::assertSame('alice@example.com', $m->extractString('email', ['email' => 'alice@example.com']));
        self::assertNull($m->extractString('roles', ['groups' => ['a', 'b']]));
        self::assertNull($m->extractString('email', ['email' => 42]));
    }
}
