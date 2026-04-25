<?php
namespace saso;

final class ConfigLoader
{
    private static $configFile;
    public static function load(string $relative=''): array
    {
        if(empty(self::$configFile)) {
            self::$configFile = ENV===null?$relative.'config.json':$relative.'config_'.ENV.'.json';
        }
        $config = json_decode(file_get_contents(self::$configFile), true);
        return self::regularization($config);
    }
    public static function regularization(array $config): array
    {
        $config['documentRoot'] = '/'.trim($config['documentRoot'], '/').'/';
        $config['programDir'] = trim($config['programDir'], '/').'/';
        $config['https'] = $config['https']===true?true:false;
        $config['logPath'] = '/'.trim($config['logPath'], '/').'/';
        return $config;
    }
}