<?php

namespace saso\util;

use saso\entity\Member;

final class AvatarHelper
{
    private const SIZE_CLASS = 'avatar avatar-xl bg-azure text-white';

    public static function render(Member $member, int $size = 96): string
    {
        $label = self::label($member);
        $url = self::validExternalImageUrl($member->__get('avatarUrl'));

        if ($url !== null) {
            return sprintf(
                '<img src="%s" alt="%s" class="rounded-circle border object-fit-cover" width="%d" height="%d" loading="lazy" referrerpolicy="no-referrer">',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                $size,
                $size,
            );
        }

        return sprintf(
            '<span class="%s" role="img" aria-label="%s"><i class="bi bi-person-circle fs-1" aria-hidden="true"></i></span>',
            self::SIZE_CLASS,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        );
    }

    public static function validExternalImageUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return $candidate;
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
}
