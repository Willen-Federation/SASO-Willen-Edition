<?php
namespace saso\util;

final class Redirect
{
    public static function url(string $to, bool $isRelative=false): string
    {
        $config = \saso\ConfigLoader::load();
        $protocol = $config['https']?'https://':'http://';
        if($isRelative) return $config['programDir'].ltrim($to, '/');
        return $protocol.$_SERVER['HTTP_HOST'].'/'.$config['programDir'].ltrim($to, '/');
    }
    public static function redirect(string $to=''): void
    {
        header('Location: '.self::url($to), true, 303,);
    }
}
