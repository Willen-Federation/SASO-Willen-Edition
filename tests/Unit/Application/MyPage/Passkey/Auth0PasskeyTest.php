<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\MyPage\Passkey;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Saso\Application\MyPage\Passkey\Auth0Passkey;

final class Auth0PasskeyTest extends TestCase
{
    public function testToTemplateRowFormatsTimestamps(): void
    {
        $passkey = new Auth0Passkey(
            id: 'passkey|abc',
            name: 'iPhone',
            createdAt: new DateTimeImmutable('2026-05-23T12:34:56+00:00', new DateTimeZone('UTC')),
            lastUsedAt: new DateTimeImmutable('2026-05-23T13:00:00+00:00', new DateTimeZone('UTC')),
        );

        $row = $passkey->toTemplateRow();

        self::assertSame('passkey|abc', $row['id']);
        self::assertSame('iPhone', $row['name']);
        self::assertSame('2026-05-23 12:34', $row['created_at']);
        self::assertSame('2026-05-23 13:00', $row['last_used_at']);
    }

    public function testToTemplateRowEmitsNullForMissingTimestamps(): void
    {
        $passkey = new Auth0Passkey(
            id: 'pk',
            name: '',
            createdAt: null,
            lastUsedAt: null,
        );

        $row = $passkey->toTemplateRow();

        self::assertNull($row['created_at']);
        self::assertNull($row['last_used_at']);
        self::assertSame('', $row['name']);
    }
}
