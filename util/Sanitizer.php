<?php
namespace saso\util;

final class Sanitizer 
{
    public static function execString(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    public static function execArray(array $array): array
    {
        return array_map(
            fn($item)=>htmlspecialchars($item, ENT_QUOTES, 'UTF-8'),
            $array
        );
    }
    public static function execMap(array $map): array
    {
        $sanitized = [];
        foreach($map as $key=>$value) {
            $sanitizedKey = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
            if (is_array($value)) {
                // Recurse into nested arrays (e.g. POST values like ai_gemini_api_keys[])
                $sanitized[$sanitizedKey] = self::execMap($value);
            } else {
                $sanitized[$sanitizedKey] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }
}
