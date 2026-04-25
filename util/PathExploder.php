<?php
namespace saso\util;

final class PathExploder
{
    /**
        '/saso/foo/bar'->['foo', 'bar']
        '/saso/'->[]
    */
    public static function exec(string $programRoot, string $request): array
    {
        $exploded = explode('/', self::trimSlash(self::removeProgramRoot($programRoot, $request)));
        return $exploded[0]===''?[]:$exploded;
    }
    /**
        '/saso/foo/bar/'->'/foo/bar'
    */
    public static function removeProgramRoot(string $programRoot, string $request): string
    {
        return preg_replace('/'.self::escapeSlash(self::trimSlash($programRoot)).'/', '', self::trimSlash($request));
    }
    /**
        '/foo/bar/'->'\/foo\/bar\/
    */
    public static function escapeSlash(string $slashed): string
    {
        return preg_replace('/\//', '\/', $slashed);
    }
    /**
        '/foo/'->'foo'
    */
    public static function trimSlash(string $slashed): string
    {
        return trim($slashed, '/');
    }
}
