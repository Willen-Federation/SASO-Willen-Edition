<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Item;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\item\RegisterFromImageDIContainer;

/*
 * The legacy upload handler delegates MIME validation to UploadValidator (covered
 * separately) and ensures a no-execute .htaccess is dropped alongside the
 * uploads/ tree. The end-to-end upload path requires a real SAPI multipart
 * request and is covered by the future integration suite; this test focuses on
 * the security boundary: the .htaccess policy must be written, must disable
 * PHP execution, and must be idempotent.
 */
#[CoversClass(RegisterFromImageDIContainer::class)]
final class RegisterFromImageDIContainerTest extends TestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/saso-uploads-'.bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->tempDir)) {
            return;
        }
        foreach (scandir($this->tempDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            @unlink($this->tempDir.'/'.$entry);
        }
        @rmdir($this->tempDir);
    }

    public function testEnsureNoExecutePolicyWritesHtaccessWhenAbsent(): void
    {
        $target = $this->tempDir.'/.htaccess';
        self::assertFileDoesNotExist($target);

        RegisterFromImageDIContainer::ensureNoExecutePolicy($this->tempDir);

        self::assertFileExists($target);
        $contents = (string) file_get_contents($target);
        // The policy must explicitly block PHP-style executables.
        self::assertStringContainsString('Require all denied', $contents);
        self::assertStringContainsString('php', $contents);
        self::assertStringContainsString('phtml', $contents);
        self::assertStringContainsString('phar', $contents);
        self::assertStringContainsString('php_flag engine off', $contents);
    }

    public function testEnsureNoExecutePolicyIsIdempotent(): void
    {
        $target = $this->tempDir.'/.htaccess';
        file_put_contents($target, "# operator-managed policy\nRequire all denied\n");
        $originalMtime = filemtime($target);
        $originalContents = (string) file_get_contents($target);

        // Sleep just past the 1-second mtime resolution so a write would change it.
        clearstatcache();
        touch($target, time() - 5);
        clearstatcache();

        RegisterFromImageDIContainer::ensureNoExecutePolicy($this->tempDir);

        clearstatcache();
        // An operator-customised .htaccess must not be overwritten.
        self::assertSame($originalContents, (string) file_get_contents($target));
    }

    public function testEnsureNoExecutePolicyNoopWhenDirMissing(): void
    {
        $missing = $this->tempDir.'/does-not-exist';
        self::assertDirectoryDoesNotExist($missing);

        // Should not throw, should not silently create the missing dir.
        RegisterFromImageDIContainer::ensureNoExecutePolicy($missing);

        self::assertDirectoryDoesNotExist($missing);
    }
}
