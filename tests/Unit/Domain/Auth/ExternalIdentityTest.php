<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\ExternalIdentity;

final class ExternalIdentityTest extends TestCase
{
    public function testStoresEveryField(): void
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');
        $id  = new ExternalIdentity(
            memberId: 'alice_001',
            authProviderId: new AuthProviderId(3),
            externalSubject: 'auth0|abcdef',
            createdAt: $now,
            updatedAt: $now,
            lastLoginAt: $now,
        );

        self::assertSame('alice_001', $id->memberId);
        self::assertSame(3, $id->authProviderId->value);
        self::assertSame('auth0|abcdef', $id->externalSubject);
        self::assertSame($now, $id->lastLoginAt);
    }

    public function testRejectsEmptyMemberId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExternalIdentity(
            memberId: '',
            authProviderId: new AuthProviderId(1),
            externalSubject: 's',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            lastLoginAt: null,
        );
    }

    public function testRejectsEmptyExternalSubject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExternalIdentity(
            memberId: 'alice_001',
            authProviderId: new AuthProviderId(1),
            externalSubject: '',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            lastLoginAt: null,
        );
    }

    public function testLastLoginIsOptional(): void
    {
        $id = new ExternalIdentity(
            memberId: 'alice_001',
            authProviderId: new AuthProviderId(1),
            externalSubject: 's',
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable(),
            lastLoginAt: null,
        );

        self::assertNull($id->lastLoginAt);
    }
}
