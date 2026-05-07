<?php

namespace saso\util;

/**
 * Renders safe profile avatars with a Bootstrap Icons fallback.
 *
 * Avatar URLs are optional user-controlled values, so this helper keeps all
 * escaping and fallback markup in one place instead of duplicating it across
 * templates.
 */
final class AvatarHelper
{
    private const ALLOWED_EXTENSIONS = '/\.(jpg|jpeg|png|webp)(\?.*)?$/i';

    private function __construct()
    {
    }

    public static function render(?string $avatarUrl, string $label, int $size = 96): string
    {
        $size = self::normalizeSize($size);
        $label = trim($label) !== '' ? $label : 'User avatar';
        $fallback = self::fallback($label, $size);
        $src = self::trustedImageUrl($avatarUrl);

        if ($src === null) {
            return $fallback;
        }

        return sprintf(
            '<span class="saso-avatar-wrap" style="width:%1$dpx;height:%1$dpx">'.
            '<img src="%2$s" alt="%3$s" class="rounded-circle border object-fit-cover" width="%1$d" height="%1$d" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.closest(\'.saso-avatar-wrap\').innerHTML=this.nextElementSibling.innerHTML">'.
            '<template>%4$s</template>'.
            '</span>',
            $size,
            self::escape($src),
            self::escape($label),
            $fallback,
        );
    }

    public static function trustedImageUrl(?string $avatarUrl): ?string
    {
        $avatarUrl = trim((string) $avatarUrl);
        if ($avatarUrl === '') {
            return null;
        }

        if (filter_var($avatarUrl, \FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($avatarUrl, PHP_URL_SCHEME));
        if (!in_array($scheme, ['https', 'http'], true)) {
            return null;
        }

        $path = (string) (parse_url($avatarUrl, PHP_URL_PATH) ?? '');
        if (preg_match(self::ALLOWED_EXTENSIONS, $path) !== 1) {
            return null;
        }

        return $avatarUrl;
    }

    private static function fallback(string $label, int $size): string
    {
        $fontSize = max(16, (int) floor($size * 0.5));

        return sprintf(
            '<span class="avatar avatar-xl bg-azure text-white rounded-circle d-inline-flex align-items-center justify-content-center border" role="img" aria-label="%1$s" style="width:%2$dpx;height:%2$dpx"><i class="bi bi-person-circle" aria-hidden="true" style="font-size:%3$dpx"></i></span>',
            self::escape($label),
            $size,
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
