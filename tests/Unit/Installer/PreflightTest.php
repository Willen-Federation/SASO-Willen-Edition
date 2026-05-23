<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\installer\Preflight;
use saso\installer\PreflightCheck;

/**
 * Coverage for the installer's preflight gate.
 *
 * Each precondition has a positive and a negative case so a regression in
 * any one check is caught independently.
 */
#[CoversClass(Preflight::class)]
#[CoversClass(PreflightCheck::class)]
final class PreflightTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/saso-preflight-'.uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpDir);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $dir.'/'.$entry;
            // Restore permissions so we can clean up.
            @chmod($full, 0700);
            if (is_dir($full)) {
                $this->rrmdir($full);
            } else {
                @unlink($full);
            }
        }
        @chmod($dir, 0700);
        @rmdir($dir);
    }

    public function testAllChecksPassOnFreshTmpdir(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $result  = Preflight::run($envPath);

        self::assertTrue(
            $result->isOk(),
            'Preflight should pass on a fresh tmpdir. Failures: '.json_encode(array_map(
                static fn (PreflightCheck $c): array => ['id' => $c->id, 'detail' => $c->detail],
                $result->failures(),
            ))
        );
        self::assertSame([], $result->failures());
    }

    public function testFailsWhenParentDirectoryMissing(): void
    {
        $envPath = $this->tmpDir.'/nonexistent/subdir/.env';
        $result  = Preflight::run($envPath);

        self::assertFalse($result->isOk());
        $ids = array_map(static fn (PreflightCheck $c): string => $c->id, $result->failures());
        self::assertContains('env_dir_exists', $ids);
        // Each failure must carry a remedy string the operator can paste.
        foreach ($result->failures() as $failure) {
            if ($failure->id === 'env_dir_exists') {
                self::assertNotNull($failure->remedy);
                self::assertStringContainsString('mkdir', (string) $failure->remedy);
            }
        }
    }

    public function testFailsWhenParentDirectoryNotWritable(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permission test not meaningful on Windows.');
        }
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('Running as root — chmod 0500 does not block writes.');
        }
        $subDir = $this->tmpDir.'/ro';
        mkdir($subDir, 0500, true);
        $envPath = $subDir.'/.env';

        $result = Preflight::run($envPath);
        self::assertFalse($result->isOk());
        $ids = array_map(static fn (PreflightCheck $c): string => $c->id, $result->failures());
        self::assertContains('env_dir_writable', $ids);

        // Cleanup helper expects a writable dir.
        chmod($subDir, 0700);
    }

    public function testFailsWhenExistingEnvNotWritable(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permission test not meaningful on Windows.');
        }
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('Running as root — chmod 0400 does not block writes.');
        }
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "FOO=bar\n");
        chmod($envPath, 0400);

        $result = Preflight::run($envPath);
        self::assertFalse($result->isOk());
        $ids = array_map(static fn (PreflightCheck $c): string => $c->id, $result->failures());
        self::assertContains('env_file_writable', $ids);

        chmod($envPath, 0600);
    }

    public function testRandomBytesCheckIncluded(): void
    {
        $result = Preflight::run($this->tmpDir.'/.env');
        $ids = array_map(static fn (PreflightCheck $c): string => $c->id, $result->checks());
        self::assertContains('random_bytes', $ids);
    }

    public function testEachFailureCarriesRemedyOrExplanation(): void
    {
        $envPath = $this->tmpDir.'/nonexistent/.env';
        $result  = Preflight::run($envPath);

        foreach ($result->failures() as $failure) {
            self::assertNotSame('', $failure->detail, 'Failure '.$failure->id.' must have a detail message.');
        }
    }
}
