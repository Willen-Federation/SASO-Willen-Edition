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
        if (preg_match('/\.(jpe?g|png|webp)$/i', $path) !== 1) {
            return null;
        }

        return $url;
    }

    private static function label(Member $member): string
    {
        $label = $member->__get('displayName') ?: $member->__get('name') ?: $member->__get('id');
        return $label.' avatar';
    }
}
