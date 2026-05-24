<?php

declare(strict_types=1);

namespace Saso\Tests\Unit;

use PHPUnit\Framework\TestCase;
use saso\ConfigLoader;
use saso\util\EnvLoader;

final class ConfigLoaderTest extends TestCase
{
    public function testEnvLoaderParsesBasicKeyValue(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_test_'.uniqid();
        mkdir($tempDir);

        try {
            $envPath = $tempDir.'/.env';
            file_put_contents($envPath, "KEY=value\nFOO=bar\n");

            $env = EnvLoader::loadFile($envPath);

            $this->assertEquals('value', $env['KEY']);
            $this->assertEquals('bar', $env['FOO']);
        } finally {
            @unlink($tempDir.'/.env');
            @rmdir($tempDir);
        }
    }

    public function testEnvLoaderIgnoresComments(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_test_'.uniqid();
        mkdir($tempDir);

        try {
            $envPath = $tempDir.'/.env';
            file_put_contents($envPath, "# Comment\nKEY=value\n# Another comment\nFOO=bar\n");

            $env = EnvLoader::loadFile($envPath);

            $this->assertCount(2, $env);
            $this->assertEquals('value', $env['KEY']);
        } finally {
            @unlink($tempDir.'/.env');
            @rmdir($tempDir);
        }
    }

    public function testEnvLoaderStripsQuotes(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_test_'.uniqid();
        mkdir($tempDir);

        try {
            $envPath = $tempDir.'/.env';
            file_put_contents($envPath, "KEY=\"double quoted\"\nFOO='single quoted'");

            $env = EnvLoader::loadFile($envPath);

            $this->assertEquals('double quoted', $env['KEY']);
            $this->assertEquals('single quoted', $env['FOO']);
        } finally {
            @unlink($tempDir.'/.env');
            @rmdir($tempDir);
        }
    }

    public function testEnvLoaderReturnsEmptyArrayForMissingFile(): void
    {
        $env = EnvLoader::loadFile('/nonexistent/path/.env');
        $this->assertEquals([], $env);
    }

    public function testEnvLoaderGetMethodWithFallbacks(): void
    {
        $env = ['EXPLICIT' => 'from_array'];

        // Test with value in array
        $result = EnvLoader::get($env, 'EXPLICIT');
        $this->assertEquals('from_array', $result);

        // Test with missing value and no default
        $result = EnvLoader::get($env, 'MISSING');
        $this->assertNull($result);

        // Test with missing value and default
        $result = EnvLoader::get($env, 'MISSING', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testEnvFileIsLoaded(): void
    {
        $projectRoot = dirname(dirname(dirname(__DIR__)));
        $envPath = $projectRoot.'/.env';

        if (file_exists($envPath)) {
            $env = EnvLoader::loadFile($envPath);
            $this->assertIsArray($env);
        } else {
            // If .env doesn't exist, loadFile should return empty array
            $env = EnvLoader::loadFile($envPath);
            $this->assertSame([], $env);
        }
    }

    public function testConfigLoaderOverlaysDocumentRootAndProgramDirFromEnv(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_config_test_'.uniqid();
        mkdir($tempDir);
        $this->resetConfigLoaderState();

        try {
            file_put_contents($tempDir.'/config.json', json_encode([
                'documentRoot' => '/production/root',
                'programDir' => '',
                'database' => [
                    'dsn' => '',
                    'user' => '',
                    'password' => '',
                ],
                'https' => false,
                'logPath' => '/tmp/log',
            ], JSON_THROW_ON_ERROR));
            file_put_contents($tempDir.'/.env', "APP_DOCUMENT_ROOT=/var/www/html\nAPP_PROGRAM_DIR=/saso/\n");

            $config = ConfigLoader::load($tempDir.'/');

            self::assertSame('/var/www/html/', $config['documentRoot']);
            self::assertSame('saso/', $config['programDir']);
        } finally {
            $this->resetConfigLoaderState();
            @unlink($tempDir.'/config.json');
            @unlink($tempDir.'/.env');
            @rmdir($tempDir);
        }
    }

    public function testConfigLoaderReturnsDefaultsWhenConfigFileMissing(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_config_test_'.uniqid();
        mkdir($tempDir);
        $this->resetConfigLoaderState();

        try {
            // No config.json — previously this caused a fatal
            // "cannot use null as array" inside overlayEnv.
            $config = ConfigLoader::load($tempDir.'/');

            self::assertIsArray($config);
            self::assertIsString($config['documentRoot']);
            self::assertIsString($config['programDir']);
            self::assertIsBool($config['https']);
            self::assertIsString($config['logPath']);
        } finally {
            $this->resetConfigLoaderState();
            @rmdir($tempDir);
        }
    }

    public function testConfigLoaderReturnsDefaultsWhenConfigFileInvalidJson(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_config_test_'.uniqid();
        mkdir($tempDir);
        $this->resetConfigLoaderState();

        try {
            file_put_contents($tempDir.'/config.json', '{not valid json');

            $config = ConfigLoader::load($tempDir.'/');

            self::assertIsArray($config);
            self::assertIsString($config['documentRoot']);
        } finally {
            $this->resetConfigLoaderState();
            @unlink($tempDir.'/config.json');
            @rmdir($tempDir);
        }
    }

    public function testConfigLoaderHandlesNonObjectJson(): void
    {
        $tempDir = sys_get_temp_dir().'/saso_config_test_'.uniqid();
        mkdir($tempDir);
        $this->resetConfigLoaderState();

        try {
            // A bare JSON literal (not an object) — json_decode succeeds but
            // returns a scalar, not an array. Must not crash.
            file_put_contents($tempDir.'/config.json', '"a string"');

            $config = ConfigLoader::load($tempDir.'/');

            self::assertIsArray($config);
        } finally {
            $this->resetConfigLoaderState();
            @unlink($tempDir.'/config.json');
            @rmdir($tempDir);
        }
    }

    private function resetConfigLoaderState(): void
    {
        $property = new \ReflectionProperty(ConfigLoader::class, 'configFile');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
