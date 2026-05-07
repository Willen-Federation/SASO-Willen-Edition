<?php

namespace saso\util;

use saso\entity\Member;

final class AvatarHelper
{
    /**
     * Returns a safe external avatar URL, or null when the profile should use
     * the built-in fallback icon.
     */
    public static function externalUrl(?string $avatarUrl): ?string
    {
        $avatarUrl = trim((string) $avatarUrl);
        if ($avatarUrl === '') {
            return null;
        }

        if (filter_var($avatarUrl, \FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($avatarUrl, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $avatarUrl;
    }

    public static function displayName(Member $member): string
    {
        $displayName = trim((string) ($member->__get('displayName') ?? ''));
        if ($displayName !== '') {
            return $displayName;
        }

        return (string) $member->__get('name');
    }

    public static function initials(Member $member): string
    {
        $name = self::displayName($member);
        $first = mb_substr($name, 0, 1);

        return $first !== '' ? mb_strtoupper($first) : '?';
    }
}
