<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\util\AvatarHelper;

#[CoversClass(AvatarHelper::class)]
final class AvatarHelperTest extends TestCase
{
    public function testTrustedImageUrlAcceptsHttpImagesOnly(): void
    {
        self::assertSame('https://example.com/avatar.webp', AvatarHelper::trustedImageUrl('https://example.com/avatar.webp'));
        self::assertSame('http://example.com/avatar.png', AvatarHelper::trustedImageUrl('http://example.com/avatar.png'));
    }

    public function testTrustedImageUrlRejectsUnsafeOrUnsupportedUrls(): void
    {
        self::assertNull(AvatarHelper::trustedImageUrl('javascript:alert(1)'));
        self::assertNull(AvatarHelper::trustedImageUrl('https://example.com/avatar.svg'));
        self::assertNull(AvatarHelper::trustedImageUrl('not-a-url'));
        self::assertNull(AvatarHelper::trustedImageUrl(''));
    }

    public function testRenderEscapesUserControlledValues(): void
    {
        $html = AvatarHelper::render('https://example.com/a.png', 'Alice "Admin" <script>', 48);

        self::assertStringContainsString('https://example.com/a.png', $html);
        self::assertStringContainsString('Alice &quot;Admin&quot; &lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testRenderFallsBackWhenUrlIsMissingOrInvalid(): void
    {
        $html = AvatarHelper::render('https://example.com/a.svg', 'Alice', 48);

        self::assertStringContainsString('bi-person-circle', $html);
        self::assertStringNotContainsString('<img ', $html);
    }
}
