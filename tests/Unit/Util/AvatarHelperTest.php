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
    public function testValidExternalImageUrlAllowsHttpImagesWithQueryStrings(): void
    {
        self::assertSame(
            'https://cdn.example.test/avatar.png?v=2',
            AvatarHelper::validExternalImageUrl('https://cdn.example.test/avatar.png?v=2'),
        );
    }

    public function testValidExternalImageUrlRejectsUnsafeOrNonImageUrls(): void
    {
        self::assertNull(AvatarHelper::validExternalImageUrl('javascript:alert(1)'));
        self::assertNull(AvatarHelper::validExternalImageUrl('https://example.test/avatar.svg'));
    }

    public function testRenderFallsBackToAccessibleIcon(): void
    {
        $member = new Member('alice_99', 'Alice', 'stored-password-hash');

        $html = AvatarHelper::render($member);

        self::assertStringContainsString('bi-person-circle', $html);
        self::assertStringContainsString('role="img"', $html);
        self::assertStringContainsString('Alice avatar', $html);
    }

    public function testRenderEscapesExternalAvatarMarkup(): void
    {
        $member = new Member(
            'alice_99',
            'Alice <Admin>',
            'stored-password-hash',
            'operator',
            'https://cdn.example.test/avatar.webp?x=<bad>',
        );

        $html = AvatarHelper::render($member);

        self::assertStringContainsString('&lt;bad&gt;', $html);
        self::assertStringContainsString('Alice &lt;Admin&gt; avatar', $html);
    }
}
