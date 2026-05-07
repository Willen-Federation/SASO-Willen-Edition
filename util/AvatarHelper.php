<?php

namespace saso\util;

use saso\entity\Member;

/**
 * Centralized, escaped avatar rendering for profile surfaces.
 */
final class AvatarHelper
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const FALLBACK_TONES = [
        'bg-azure',
        'bg-blue',
        'bg-indigo',
        'bg-purple',
        'bg-pink',
        'bg-red',
        'bg-orange',
        'bg-yellow',
        'bg-lime',
        'bg-green',
        'bg-teal',
        'bg-cyan',
    ];

    private function __construct()
    {
    }

    public static function render(Member|string|null $memberOrUrl, string|int|null $labelOrSize = null, int $size = 96): string
    {
        if ($memberOrUrl instanceof Member) {
            $member = $memberOrUrl;
            $label = self::label($member);
            $url = self::imageUrl($member->__get('avatarUrl'));
            $size = is_int($labelOrSize) ? $labelOrSize : $size;
        } else {
            $label = is_string($labelOrSize) ? $labelOrSize : 'User avatar';
            $url = self::imageUrl($memberOrUrl);
        }

        $size = self::normalizeSize($size);
        $label = trim($label) !== '' ? $label : 'User avatar';
        $fallback = self::fallback($label, $size);

        if ($url === null) {
            return $fallback;
        }

        return sprintf(
            '<span class="saso-avatar-wrap" style="width:%1$dpx;height:%1$dpx">'.
            '<img src="%2$s" alt="%3$s" class="rounded-circle border object-fit-cover saso-avatar-image" width="%1$d" height="%1$d" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.closest(\'.saso-avatar-wrap\').innerHTML=this.nextElementSibling.innerHTML">'.
            '<template>%4$s</template>'.
            '</span>',
            $size,
            self::escape($url),
            self::escape($label),
            $fallback,
        );
    }

    public static function imageUrl(?string $url): ?string
    {
        return self::validExternalImageUrl($url);
    }

    public static function externalUrl(?string $url): ?string
    {
        return self::validExternalImageUrl($url);
    }

    public static function trustedImageUrl(?string $url): ?string
    {
        return self::validExternalImageUrl($url);
    }

    public static function validExternalImageUrl(?string $url): ?string
    {
        $candidate = trim((string) $url);
        if ($candidate === '' || preg_match('/\s/', $candidate) === 1) {
            return null;
        }

        $parts = parse_url($candidate);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (empty($parts['host'])) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($parts['path'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return $candidate;
    }

    public static function displayName(Member $member): string
    {
        $displayName = trim((string) ($member->__get('displayName') ?? ''));
        if ($displayName !== '') {
            return $displayName;
        }

        return (string) $member->__get('name');
    }

    public static function fallbackTone(?string $seed): string
    {
        $normalized = trim((string) $seed);
        if ($normalized === '') {
            return self::FALLBACK_TONES[0];
        }

        $index = abs(crc32($normalized)) % count(self::FALLBACK_TONES);
        return self::FALLBACK_TONES[$index];
    }

    public static function fallbackIconClass(): string
    {
        return 'bi bi-person-circle fs-1';
    }

    private static function label(Member $member): string
    {
        return self::displayName($member).' avatar';
    }

    private static function fallback(string $label, int $size): string
    {
        $fontSize = max(16, (int) floor($size * 0.5));
        $tone = self::fallbackTone($label);

        return sprintf(
            '<span class="avatar avatar-xl %1$s text-white rounded-circle d-inline-flex align-items-center justify-content-center border" role="img" aria-label="%2$s" style="width:%3$dpx;height:%3$dpx"><i class="%4$s" aria-hidden="true" style="font-size:%5$dpx"></i></span>',
            self::escape($tone),
            self::escape($label),
            $size,
            self::escape(self::fallbackIconClass()),
            $fontSize,
        );
    }

    private static function normalizeSize(int $size): int
    {
        return max(32, min(256, $size));
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
