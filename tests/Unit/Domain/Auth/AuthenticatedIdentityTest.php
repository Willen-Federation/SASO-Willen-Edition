<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthenticatedIdentity;
use Saso\Domain\Auth\AuthProviderId;

final class AuthenticatedIdentityTest extends TestCase
{
    public function testStoresAllFields(): void
    {
        $id = new AuthenticatedIdentity(
            authProviderId: new AuthProviderId(3),
            externalSubject: 'auth0|abcdef',
            email: 'alice@example.com',
            displayName: 'Alice Liddell',
            claims: ['sub' => 'auth0|abcdef', 'groups' => ['admin']],
        );

        self::assertSame(3, $id->authProviderId->value);
        self::assertSame('auth0|abcdef', $id->externalSubject);
        self::assertSame('alice@example.com', $id->email);
        self::assertSame('Alice Liddell', $id->displayName);
        self::assertSame(['admin'], $id->claims['groups']);
    }

    public function testRejectsEmptyExternalSubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('externalSubject must not be empty');

        new AuthenticatedIdentity(
            authProviderId: new AuthProviderId(1),
            externalSubject: '',
            email: 'x@y',
            displayName: 'X',
        );
    }

    public function testClaimsDefaultToEmptyArray(): void
    {
        $id = new AuthenticatedIdentity(
            authProviderId: new AuthProviderId(1),
            externalSubject: 's',
            email: 'x@y',
            displayName: 'X',
        );

        self::assertSame([], $id->claims);
    }
}
