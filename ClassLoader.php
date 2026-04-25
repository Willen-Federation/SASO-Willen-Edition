<?php

namespace saso;

final class ClassLoader
{
    public static function load(array $config): \Closure
    {
        return function(string $ns) use ($config): void
        {
            $path = self::convert(
                $ns,
                self::removeDomain($config['domainDepth']),
                self::changeSeparator(DIRECTORY_SEPARATOR),
                self::addExtension($config['phpExtension']),
                self::addDocumentRoot($config['programDir']),
                self::addDocumentRoot($config['documentRoot']),
            );
            if(!file_exists($path)) return;
            require $path;
        };
    }
    /**
    * @param string $ns
    * @param fn(string $ns):string $converters
    */
    public static function convert(string $ns, \Closure ...$converters): string
    {
        return array_reduce(
            $converters,
            fn($carry, $item)=>$item($carry),
            $ns
        );
    }
    public static function removeDomain(int $domainDepth): \Closure
    {
        $remove = fn(string $ns)=>substr($ns, 1+strpos($ns, '\\'));
        return fn(string $ns):string=>array_reduce(
            range(0,$domainDepth),
            fn($carry, $item)=>$item?$remove($carry):$carry,
            $ns
        );
    }
    public static function changeSeparator(string $separator): \Closure
    {
        return fn(string $ns):string=>$separator.ltrim(array_reduce(
            explode('\\', $ns),
            fn($carry, $item)=>$carry.$separator.$item, ''
        ), $separator);
    }
    public static function addExtension(string $extension): \Closure
    {
        return fn(string $ns):string=>$ns.'.'.ltrim($extension, '.');
    }
    public static function addDocumentRoot(string $documentRoot): \Closure
    {
        return fn(string $ns):string=>'/'.trim($documentRoot, '/').$ns;
    }
}
