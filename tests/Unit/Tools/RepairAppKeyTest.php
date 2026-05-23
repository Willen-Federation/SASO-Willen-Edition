<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Tools;

use PHPUnit\Framework\TestCase;

/**
 * Smoke tests for tools/repair-app-key.php.
 *
 * Spawns the script as a subprocess so we exercise the real CLI entry point
 * (arg parsing, exit code, --env-path override, dry-run guard). Full behaviour
 * coverage lives in EnvWriterTest; here we only verify the orchestration.
 */
final class RepairAppKeyTest extends TestCase
{
    private string $tmpDir;
    private string $envPath;
    private string $scriptPath;

    protected function setUp(): void
    {
        $this->tmpDir     = sys_get_temp_dir().'/saso-repair-'.uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
        $this->envPath    = $this->tmpDir.'/.env';
        $this->scriptPath = dirname(__DIR__, 3).'/tools/repair-app-key.php';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($this->tmpDir);
    }

    /**
     * @param list<string> $args
     *
     * @return array{stdout:string,stderr:string,exit:int}
     */
    private function runScript(array $args): array
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open not available.');
        }
        $cmd = array_merge([PHP_BINARY, $this->scriptPath], $args);
        $proc = proc_open(
            $cmd,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($proc)) {
            self::fail('Could not spawn repair-app-key.php.');
        }
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        return ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => $exit];
    }

    public function testHelpFlagExitsZero(): void
    {
        $result = $this->runScript(['--help']);
        self::assertSame(0, $result['exit']);
        self::assertStringContainsString('Usage: php tools/repair-app-key.php', $result['stdout']);
    }

    public function testInvalidKeyFlagExitsTwo(): void
    {
        $result = $this->runScript(['--key=nonsense', '--env-path='.$this->envPath]);
        self::assertSame(2, $result['exit']);
        self::assertStringContainsString('Invalid --key value', $result['stderr']);
    }

    public function testUnknownFlagExitsTwo(): void
    {
        $result = $this->runScript(['--banana', '--env-path='.$this->envPath]);
        self::assertSame(2, $result['exit']);
        self::assertStringContainsString('Unknown flag', $result['stderr']);
    }

    public function testDryRunDoesNotWriteFile(): void
    {
        // No .env yet; dry-run should report "would change" for everything
        // but must NOT create the file.
        $result = $this->runScript(['--dry-run', '--env-path='.$this->envPath]);

        self::assertSame(0, $result['exit'], 'Dry-run on missing file should still exit 0. STDERR: '.$result['stderr']);
        self::assertFileDoesNotExist($this->envPath, 'Dry-run must not create .env.');
        self::assertStringContainsString('Dry-run', $result['stdout']);
        self::assertStringContainsString('would change', $result['stdout']);
    }

    public function testWriteCreatesValidEnvWhenMissing(): void
    {
        $result = $this->runScript(['--env-path='.$this->envPath]);
        self::assertSame(0, $result['exit'], 'STDERR: '.$result['stderr']);
        self::assertFileExists($this->envPath);

        $contents = file_get_contents($this->envPath);
        self::assertNotFalse($contents);
        self::assertMatchesRegularExpression('/^APP_KEY=[A-Za-z0-9+\/=]{44}$/m', $contents);
        self::assertMatchesRegularExpression('/^JWT_SECRET=[A-Za-z0-9+\/=]{44}$/m', $contents);
        self::assertMatchesRegularExpression('/^WEBHOOK_SECRET=[A-Za-z0-9+\/=]{44}$/m', $contents);
    }

    public function testIdempotentWhenAllValuesAlreadyValid(): void
    {
        // Seed with valid values, run without --force: nothing should change.
        $appKey  = base64_encode(random_bytes(32));
        $jwt     = base64_encode(random_bytes(32));
        $webhook = bin2hex(random_bytes(32));
        file_put_contents(
            $this->envPath,
            "APP_KEY=$appKey\nJWT_SECRET=$jwt\nWEBHOOK_SECRET=$webhook\n",
        );
        $before = file_get_contents($this->envPath);

        $result = $this->runScript(['--env-path='.$this->envPath]);
        self::assertSame(0, $result['exit']);
        self::assertSame($before, file_get_contents($this->envPath));
        self::assertStringContainsString('preserved', $result['stdout']);
    }

    public function testForceRegeneratesAndBacksUp(): void
    {
        $appKey = base64_encode(random_bytes(32));
        file_put_contents($this->envPath, "APP_KEY=$appKey\n");

        $result = $this->runScript([
            '--force',
            '--key=app_key',
            '--env-path='.$this->envPath,
        ]);
        self::assertSame(0, $result['exit']);
        self::assertStringContainsString('forced regeneration', $result['stdout']);

        $newKey = $this->readEnvKey('APP_KEY');
        self::assertNotSame($appKey, $newKey, 'APP_KEY should have changed under --force.');

        $backups = glob($this->envPath.'.backup.*') ?: [];
        self::assertNotEmpty($backups, 'A backup file should exist after --force.');
    }

    private function readEnvKey(string $key): ?string
    {
        $contents = file_get_contents($this->envPath);
        if ($contents === false) {
            return null;
        }
        foreach (explode("\n", $contents) as $line) {
            if (str_starts_with($line, $key.'=')) {
                return substr($line, strlen($key) + 1);
            }
        }
        return null;
    }
}
