<?php

namespace saso\util;

/**
 * Helpers for rendering externally hosted member avatars safely.
 */
final class AvatarHelper
{
    private const ALLOWED_SCHEMES = ['http', 'https'];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const FALLBACK_TONES = ['bg-azure', 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-orange', 'bg-green'];

    private function __construct()
    {
    }

    public static function imageUrl(?string $url): ?string
    {
        $candidate = trim((string) $url);
        if ($candidate === '') {
            return null;
        }

        if (filter_var($candidate, \FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($candidate);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
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
