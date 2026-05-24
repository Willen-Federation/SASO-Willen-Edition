<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use saso\util\EnvLoader;
use saso\util\EnvWriter;

#[CoversClass(EnvWriter::class)]
final class EnvWriterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/saso-envwriter-'.uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        // Allow nested tmp directories from concurrent-write test.
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
            if (is_dir($full)) {
                $this->rrmdir($full);
            } else {
                @unlink($full);
            }
        }
        @rmdir($dir);
    }

    // ── Instance API ────────────────────────────────────────────────────────

    public function testSetOrUpdateCreatesFileFromExampleWhenMissing(): void
    {
        $envPath     = $this->tmpDir.'/.env';
        $examplePath = $this->tmpDir.'/.env.example';
        file_put_contents($examplePath, "# Example\nDB_USER=alice\n");

        $writer = new EnvWriter();
        $writer->setOrUpdate('APP_KEY', 'value123', $envPath);

        self::assertFileExists($envPath);
        $parsed = EnvLoader::loadFile($envPath);
        self::assertSame('alice', $parsed['DB_USER']);
        self::assertSame('value123', $parsed['APP_KEY']);
    }

    public function testSetOrUpdateCreatesEmptyFileWhenNoExample(): void
    {
        $envPath = $this->tmpDir.'/.env';

        $writer = new EnvWriter();
        $writer->setOrUpdate('FOO', 'bar', $envPath);

        $contents = file_get_contents($envPath);
        self::assertNotFalse($contents);
        self::assertStringContainsString('FOO=bar', $contents);
    }

    public function testSetOrUpdatePreservesOrderAndSurroundingLines(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $initial = <<<ENV
# top comment
DB_USER=alice

# section header
APP_KEY=oldvalue
JWT_SECRET=oldjwt
ENV;
        file_put_contents($envPath, $initial."\n");

        $writer = new EnvWriter();
        $writer->setOrUpdate('APP_KEY', 'newvalue', $envPath);

        $contents = file_get_contents($envPath);
        self::assertNotFalse($contents);

        // Order preserved: comments and other keys still in original position.
        self::assertStringContainsString("# top comment\nDB_USER=alice", $contents);
        self::assertStringContainsString("# section header\nAPP_KEY=newvalue", $contents);
        self::assertStringContainsString("APP_KEY=newvalue\nJWT_SECRET=oldjwt", $contents);
        self::assertStringNotContainsString('oldvalue', $contents);
    }

    public function testSetOrUpdateAppendsWhenKeyMissing(): void
    {
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "DB_USER=alice\n");

        $writer = new EnvWriter();
        $writer->setOrUpdate('NEW_KEY', 'newval', $envPath);

        $parsed = EnvLoader::loadFile($envPath);
        self::assertSame('alice', $parsed['DB_USER']);
        self::assertSame('newval', $parsed['NEW_KEY']);

        $contents = file_get_contents($envPath);
        self::assertNotFalse($contents);
        self::assertStringContainsString("DB_USER=alice\nNEW_KEY=newval\n", $contents);
    }

    public function testSetOrUpdateQuotesValueWithSpaces(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();
        $writer->setOrUpdate('LABEL', 'has spaces here', $envPath);

        $contents = file_get_contents($envPath);
        self::assertNotFalse($contents);
        self::assertStringContainsString('LABEL="has spaces here"', $contents);
        // Roundtrip via the loader.
        self::assertSame('has spaces here', EnvLoader::loadFile($envPath)['LABEL']);
    }

    public function testSetOrUpdateHandlesValueWithEqualsSign(): void
    {
        // EnvLoader splits on the FIRST `=`, so DSN-style values do not need
        // quoting. Roundtripping must preserve the value verbatim either way.
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();
        $writer->setOrUpdate('DB_DSN', 'mysql:host=localhost;dbname=saso', $envPath);

        self::assertSame(
            'mysql:host=localhost;dbname=saso',
            EnvLoader::loadFile($envPath)['DB_DSN']
        );
    }

    public function testSetOrUpdateQuotesValueWithHash(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();
        $writer->setOrUpdate('NOTE', 'a#b', $envPath);

        $contents = file_get_contents($envPath);
        self::assertNotFalse($contents);
        self::assertStringContainsString('NOTE="a#b"', $contents);
        self::assertSame('a#b', EnvLoader::loadFile($envPath)['NOTE']);
    }

    public function testSetOrUpdateEscapesEmbeddedDoubleQuote(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();
        $writer->setOrUpdate('Q', 'a"b', $envPath);

        $contents = file_get_contents($envPath);
        self::assertNotFalse($contents);
        // The literal stored form must be `Q="a\"b"`.
        self::assertStringContainsString('Q="a\\"b"', $contents);
    }

    public function testSetOrUpdateRejectsNewlines(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();

        $this->expectException(InvalidArgumentException::class);
        $writer->setOrUpdate('FOO', "line1\nline2", $envPath);
    }

    public function testSetOrUpdateRejectsCarriageReturns(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();

        $this->expectException(InvalidArgumentException::class);
        $writer->setOrUpdate('FOO', "line1\rline2", $envPath);
    }

    public function testSetOrUpdateRejectsNullBytes(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();

        $this->expectException(InvalidArgumentException::class);
        $writer->setOrUpdate('FOO', "a\0b", $envPath);
    }

    public function testSetOrUpdateRejectsInvalidKey(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();

        $this->expectException(InvalidArgumentException::class);
        $writer->setOrUpdate('1BAD-KEY', 'x', $envPath);
    }

    public function testSetOrUpdateFailsWhenParentDirectoryMissing(): void
    {
        $envPath = $this->tmpDir.'/missing/.env';
        $writer  = new EnvWriter();

        $this->expectException(RuntimeException::class);
        $writer->setOrUpdate('FOO', 'bar', $envPath);
    }

    public function testSetOrUpdateLeavesNoTempFileBehindOnSuccess(): void
    {
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();
        $writer->setOrUpdate('FOO', 'bar', $envPath);

        self::assertFileDoesNotExist($envPath.'.tmp');
    }

    public function testSetOrUpdateChmods0600(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permission test not meaningful on Windows.');
        }
        $envPath = $this->tmpDir.'/.env';
        $writer  = new EnvWriter();
        $writer->setOrUpdate('FOO', 'bar', $envPath);

        $mode = fileperms($envPath) & 0777;
        self::assertSame(0600, $mode, sprintf('Expected 0600, got 0%o', $mode));
    }

    public function testGetReturnsNullWhenFileMissing(): void
    {
        $writer = new EnvWriter();
        self::assertNull($writer->get('APP_KEY', $this->tmpDir.'/.env'));
    }

    public function testGetReturnsNullWhenKeyAbsent(): void
    {
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "DB_USER=alice\n");

        $writer = new EnvWriter();
        self::assertNull($writer->get('APP_KEY', $envPath));
    }

    public function testGetReturnsValueWhenPresent(): void
    {
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "APP_KEY=abc123\n");

        $writer = new EnvWriter();
        self::assertSame('abc123', $writer->get('APP_KEY', $envPath));
    }

    public function testHasValidValueRunsValidator(): void
    {
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "APP_KEY=short\nWEBHOOK=".str_repeat('a', 64)."\n");

        $writer    = new EnvWriter();
        $atLeast32 = static fn (string $v): bool => strlen($v) >= 32;

        self::assertFalse($writer->hasValidValue('APP_KEY', $envPath, $atLeast32));
        self::assertTrue($writer->hasValidValue('WEBHOOK', $envPath, $atLeast32));
        self::assertFalse($writer->hasValidValue('MISSING', $envPath, $atLeast32));
    }

    public function testConcurrentWritesDoNotCorruptFile(): void
    {
        // Smoke test for LOCK_EX: spawn two child PHP processes that each set
        // a different key on the same file 25 times. After both finish, both
        // keys must be present, the seed key must survive, and the file must
        // still parse cleanly.
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open not available.');
        }

        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "SEED=1\n");

        $projectRoot = dirname(__DIR__, 3);
        $scriptPath  = $this->tmpDir.'/concurrent-writer.php';
        $script      = '<?php'."\n"
            .'require '.var_export($projectRoot.'/util/EnvLoader.php', true).';'."\n"
            .'require '.var_export($projectRoot.'/util/EnvWriter.php', true).';'."\n"
            .'$writer = new saso\util\EnvWriter();'."\n"
            .'for ($i = 0; $i < 25; $i++) {'."\n"
            .'    $writer->setOrUpdate($argv[2], "iter-" . $i, $argv[1]);'."\n"
            .'}'."\n";
        file_put_contents($scriptPath, $script);

        $php   = PHP_BINARY;
        $procs = [];
        foreach (['WRITER_A', 'WRITER_B'] as $key) {
            $p = proc_open(
                [$php, $scriptPath, $envPath, $key],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            if (!is_resource($p)) {
                self::markTestSkipped('Could not spawn child processes.');
            }
            $procs[] = ['proc' => $p, 'pipes' => $pipes];
        }
        $errors = [];
        foreach ($procs as $entry) {
            $stderr = '';
            if (isset($entry['pipes'][2]) && is_resource($entry['pipes'][2])) {
                $stderr = stream_get_contents($entry['pipes'][2]) ?: '';
            }
            foreach ($entry['pipes'] as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            $exit = proc_close($entry['proc']);
            if ($exit !== 0) {
                $errors[] = "exit=$exit stderr=".trim($stderr);
            }
        }
        self::assertSame([], $errors, 'Child writer failed: '.implode(' | ', $errors));

        $parsed = EnvLoader::loadFile($envPath);
        self::assertArrayHasKey('SEED', $parsed, 'Seed key was clobbered.');
        self::assertArrayHasKey('WRITER_A', $parsed, 'Writer A lost.');
        self::assertArrayHasKey('WRITER_B', $parsed, 'Writer B lost.');
        self::assertSame('1', $parsed['SEED']);
        self::assertMatchesRegularExpression('/^iter-\d+$/', $parsed['WRITER_A']);
        self::assertMatchesRegularExpression('/^iter-\d+$/', $parsed['WRITER_B']);
    }

    public function testSetOrUpdateDoesNotLeaveWorldReadableWindow(): void
    {
        // After a rename(), the new inode used to inherit the umask-default
        // mode (often 0644) until the explicit chmod 0600 fired. We now chmod
        // the temp file *before* rename. Verify the file is never world-readable
        // post-rename by checking the post-state mode exactly.
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permission test not meaningful on Windows.');
        }
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "SEED=1\n");
        @chmod($envPath, 0644);

        $writer = new EnvWriter();
        $writer->setOrUpdate('FOO', 'bar', $envPath);

        $mode = fileperms($envPath) & 0777;
        self::assertSame(0600, $mode, sprintf('Expected 0600, got 0%o', $mode));
    }

    // ── Legacy static API (regression coverage) ────────────────────────────

    public function testLegacySetCreatesFileAndAppendsLine(): void
    {
        $envPath = $this->tmpDir.'/.env';
        self::assertTrue(EnvWriter::set($envPath, 'FOO', 'bar'));
        self::assertSame('bar', EnvLoader::loadFile($envPath)['FOO']);
    }

    public function testLegacySetReplacesExistingLine(): void
    {
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "FOO=old\n");
        self::assertTrue(EnvWriter::set($envPath, 'FOO', 'new'));
        self::assertSame('new', EnvLoader::loadFile($envPath)['FOO']);
    }

    public function testLegacySetRejectsInvalidKey(): void
    {
        $envPath = $this->tmpDir.'/.env';
        self::assertFalse(EnvWriter::set($envPath, '1BAD', 'x'));
    }

    public function testLegacySetCreatesFileWith0600Mode(): void
    {
        // Regression for audit/installer-fixes: the legacy static API used to
        // create .env at 0640. The installer first writes DB credentials via
        // this path, so a wider mode meant DB_PASSWORD was readable by every
        // local user from the first wizard step onward. 0600 matches the
        // instance API and aligns with WizardState::ensureEnvFile().
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permission test not meaningful on Windows.');
        }
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('Running as root — mode comparison unreliable under umask 0.');
        }
        $envPath = $this->tmpDir.'/.env';
        self::assertTrue(EnvWriter::set($envPath, 'FOO', 'bar'));

        $mode = fileperms($envPath) & 0777;
        self::assertSame(0600, $mode, sprintf('Expected 0600, got 0%o', $mode));
    public function testLegacySetRejectsNullBytes(): void
    {
        // Null byte injection would silently truncate the .env line at parse
        // time, letting one value swallow the next on subsequent reads.
        $envPath = $this->tmpDir.'/.env';
        self::assertFalse(EnvWriter::set($envPath, 'FOO', "a\0b"));
    }

    public function testLegacySetIsAtomic(): void
    {
        // The legacy static API used to call file_put_contents() directly on
        // the live .env, which truncates-then-writes. A crash mid-write would
        // leave a corrupt .env. Verify the new tmp+rename path leaves no
        // .tmp.* sibling behind on success, and that the file is never empty.
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "EXISTING=1\n");

        self::assertTrue(EnvWriter::set($envPath, 'NEW', 'value'));

        // No stray temp files.
        $strays = glob($envPath.'.tmp.*') ?: [];
        self::assertSame([], $strays, 'Atomic write left stray temp files: '.implode(', ', $strays));

        // Original content preserved.
        $parsed = EnvLoader::loadFile($envPath);
        self::assertSame('1', $parsed['EXISTING']);
        self::assertSame('value', $parsed['NEW']);
    }

    public function testLegacySetPreservesFileMode(): void
    {
        // When the operator has already set restrictive permissions on .env,
        // a subsequent legacy set() must not silently loosen them via the
        // tmp+rename path.
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX permission test not meaningful on Windows.');
        }
        $envPath = $this->tmpDir.'/.env';
        file_put_contents($envPath, "EXISTING=1\n");
        @chmod($envPath, 0600);

        self::assertTrue(EnvWriter::set($envPath, 'NEW', 'value'));

        $mode = fileperms($envPath) & 0777;
        self::assertSame(0600, $mode, sprintf('Expected mode 0600 preserved, got 0%o', $mode));
    }
}
