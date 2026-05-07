<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\entity\Member;
use saso\util\AvatarHelper;

#[CoversClass(AvatarHelper::class)]
final class AvatarHelperTest extends TestCase
{
    public function testExternalUrlAcceptsHttpAndHttpsUrls(): void
    {
        self::assertSame('https://example.com/avatar.png', AvatarHelper::externalUrl(' https://example.com/avatar.png '));
        self::assertSame('http://example.com/avatar.png', AvatarHelper::externalUrl('http://example.com/avatar.png'));
    }

    public function testExternalUrlRejectsUnsafeOrInvalidUrls(): void
    {
        self::assertNull(AvatarHelper::externalUrl(null));
        self::assertNull(AvatarHelper::externalUrl(''));
        self::assertNull(AvatarHelper::externalUrl('javascript:alert(1)'));
        self::assertNull(AvatarHelper::externalUrl('not a url'));
    }

    public function testDisplayNamePrefersProfileDisplayName(): void
    {
        $member = new Member('alice_99', 'Alice Login', 'hash', 'operator', displayName: 'Alice Profile');

        self::assertSame('Alice Profile', AvatarHelper::displayName($member));
    }

    public function testDisplayNameFallsBackToMemberName(): void
    {
        $member = new Member('alice_99', 'Alice Login', 'hash');

        self::assertSame('Alice Login', AvatarHelper::displayName($member));
    }
}
