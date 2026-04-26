<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\util\EnvLoader;

#[CoversClass(EnvLoader::class)]
final class EnvLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/saso-envloader-'.uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->tmpDir);
    }

    public function testLoadFileReturnsEmptyArrayForMissingFile(): void
    {
        $missing = $this->tmpDir.'/does-not-exist';
        self::assertSame([], EnvLoader::loadFile($missing));
    }

    public function testLoadFileParsesPlainKeyValuePairs(): void
    {
        $env = $this->writeEnv("DB_USER=alice\nDB_HOST=localhost\n");
        self::assertSame(
            ['DB_USER' => 'alice', 'DB_HOST' => 'localhost'],
            EnvLoader::loadFile($env),
        );
    }

    public function testLoadFileSkipsCommentsAndBlankLines(): void
    {
        $env = $this->writeEnv("# top comment\n\nDB_USER=alice\n   # indented comment\nDB_HOST=localhost\n");
        self::assertSame(
            ['DB_USER' => 'alice', 'DB_HOST' => 'localhost'],
            EnvLoader::loadFile($env),
        );
    }

    public function testLoadFileStripsMatchingDoubleAndSingleQuotes(): void
    {
        $env = $this->writeEnv("DB_USER=\"al ice\"\nDB_PASSWORD='p@ss=word'\nRAW=plain\n");
        self::assertSame(
            ['DB_USER' => 'al ice', 'DB_PASSWORD' => 'p@ss=word', 'RAW' => 'plain'],
            EnvLoader::loadFile($env),
        );
    }

    public function testLoadFileDoesNotStripUnmatchedQuotes(): void
    {
        $env = $this->writeEnv("MIXED=\"unbalanced'\n");
        self::assertSame(['MIXED' => '"unbalanced\''], EnvLoader::loadFile($env));
    }

    public function testLoadFileIgnoresInvalidKeys(): void
    {
        $env = $this->writeEnv("=lonely_value\n1BAD=ok?\nGOOD_KEY=value\n");
        self::assertSame(['GOOD_KEY' => 'value'], EnvLoader::loadFile($env));
    }

    public function testLoadFilePreservesTrailingValueCharacters(): void
    {
        $env = $this->writeEnv("DSN=mysql:host=localhost;dbname=saso;charset=utf8mb4\n");
        self::assertSame(
            ['DSN' => 'mysql:host=localhost;dbname=saso;charset=utf8mb4'],
            EnvLoader::loadFile($env),
        );
    }

    public function testGetPrefersExplicitEnvOverGetenv(): void
    {
        putenv('SASO_TEST_KEY_A=from-process');
        try {
            self::assertSame(
                'from-array',
                EnvLoader::get(['SASO_TEST_KEY_A' => 'from-array'], 'SASO_TEST_KEY_A'),
            );
        } finally {
            putenv('SASO_TEST_KEY_A');
        }
    }

    public function testGetFallsThroughToGetenvWhenArrayLacksKey(): void
    {
        putenv('SASO_TEST_KEY_B=process-value');
        try {
            self::assertSame(
                'process-value',
                EnvLoader::get([], 'SASO_TEST_KEY_B'),
            );
        } finally {
            putenv('SASO_TEST_KEY_B');
        }
    }

    public function testGetReturnsDefaultWhenNeitherSourceHasKey(): void
    {
        self::assertSame('fallback', EnvLoader::get([], 'SASO_UNSET_KEY', 'fallback'));
        self::assertNull(EnvLoader::get([], 'SASO_UNSET_KEY'));
    }

    public function testGetTreatsEmptyStringFromEnvFileAsValue(): void
    {
        // Distinguishing "set to empty" from "unset" matters for password fields
        // — an operator may intentionally clear a value via .env.
        self::assertSame('', EnvLoader::get(['SASO_EMPTY' => ''], 'SASO_EMPTY', 'default'));
    }

    private function writeEnv(string $contents): string
    {
        $path = $this->tmpDir.'/'.uniqid('env-', true);
        file_put_contents($path, $contents);
        return $path;
    }
}
