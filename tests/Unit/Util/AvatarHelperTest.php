<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\util\AvatarHelper;

#[CoversClass(AvatarHelper::class)]
final class AvatarHelperTest extends TestCase
{
    public function testImageUrlAcceptsHttpsImageWithQueryString(): void
    {
        self::assertSame(
            'https://cdn.example.com/avatar/profile.webp?size=96',
            AvatarHelper::imageUrl(' https://cdn.example.com/avatar/profile.webp?size=96 '),
        );
    }

    public function testImageUrlRejectsUnsupportedSchemesAndFileTypes(): void
    {
        self::assertNull(AvatarHelper::imageUrl('javascript:alert(1)'));
        self::assertNull(AvatarHelper::imageUrl('ftp://example.com/avatar.png'));
        self::assertNull(AvatarHelper::imageUrl('https://example.com/avatar.svg'));
        self::assertNull(AvatarHelper::imageUrl('not-a-url'));
    }

    public function testFallbackToneIsStableForSameSeed(): void
    {
        self::assertSame(AvatarHelper::fallbackTone('Alice'), AvatarHelper::fallbackTone('Alice'));
        self::assertMatchesRegularExpression('/^bg-[a-z]+$/', AvatarHelper::fallbackTone('Alice'));
    }

    public function testFallbackIconClassUsesPersonCircleIcon(): void
    {
        self::assertStringContainsString('bi-person-circle', AvatarHelper::fallbackIconClass());
    }
}
