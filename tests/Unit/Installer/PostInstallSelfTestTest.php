<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\installer\PostInstallSelfTest;
use saso\installer\SecurityStep;
use saso\installer\SelfTestResult;

/**
 * Verifies that the wizard's post-install self-test correctly distinguishes
 * a healthy `.env` from one that would crash the API boot. The HTTP probe is
 * stubbed so the test does not need a running server.
 */
#[CoversClass(PostInstallSelfTest::class)]
#[CoversClass(SelfTestResult::class)]
final class PostInstallSelfTestTest extends TestCase
{
    private string $tmpDir;
    private string $envPath;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir().'/saso-selftest-'.uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
        $this->envPath = $this->tmpDir.'/.env';
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

    private function seedHealthyEnv(): void
    {
        $appKey  = base64_encode(random_bytes(32));
        $jwt     = base64_encode(random_bytes(32));
        $webhook = bin2hex(random_bytes(32));
        file_put_contents(
            $this->envPath,
            "APP_KEY=$appKey\nJWT_SECRET=$jwt\nWEBHOOK_SECRET=$webhook\n"
        );
    }

    public function testReturnsOkForHealthyEnv(): void
    {
        $this->seedHealthyEnv();
        $test = new PostInstallSelfTest();

        $result = $test->run($this->envPath);
        self::assertTrue($result->ok, 'Healthy .env should pass self-test. Failures: '.json_encode($result->failures));
    }

    public function testReturnsFailedWhenAppKeyMissing(): void
    {
        file_put_contents(
            $this->envPath,
            'JWT_SECRET='.base64_encode(random_bytes(32))."\nWEBHOOK_SECRET=".bin2hex(random_bytes(32))."\n"
        );
        $test = new PostInstallSelfTest();

        $result = $test->run($this->envPath);
        self::assertFalse($result->ok);
        $keys = array_map(static fn (array $f): string => $f['key'], $result->failures);
        self::assertContains(SecurityStep::KEY_APP, $keys);
    }

    public function testReturnsFailedWhenJwtSecretTooShort(): void
    {
        $appKey  = base64_encode(random_bytes(32));
        $webhook = bin2hex(random_bytes(32));
        file_put_contents(
            $this->envPath,
            "APP_KEY=$appKey\nJWT_SECRET=tooShort\nWEBHOOK_SECRET=$webhook\n"
        );
        $test = new PostInstallSelfTest();

        $result = $test->run($this->envPath);
        self::assertFalse($result->ok);
        $keys = array_map(static fn (array $f): string => $f['key'], $result->failures);
        self::assertContains(SecurityStep::KEY_JWT, $keys);
    }

    public function testReturnsFailedWhenEnvFileMissing(): void
    {
        $test   = new PostInstallSelfTest();
        $result = $test->run($this->tmpDir.'/missing.env');
        self::assertFalse($result->ok);
    }

    public function testHttpProbeFailureIsCarriedThrough(): void
    {
        $this->seedHealthyEnv();
        $probe = static fn (string $url): array => ['ok' => false, 'status' => 500, 'error' => 'simulated 500'];
        $test  = new PostInstallSelfTest($probe);

        $result = $test->run($this->envPath, ['http://127.0.0.1/api/v1/health']);
        self::assertFalse($result->ok);
        $keys = array_map(static fn (array $f): string => $f['key'], $result->failures);
        self::assertContains('http:http://127.0.0.1/api/v1/health', $keys);
    }

    public function testHttpProbeSuccessKeepsResultOk(): void
    {
        $this->seedHealthyEnv();
        $probe = static fn (string $url): array => ['ok' => true, 'status' => 200, 'error' => null];
        $test  = new PostInstallSelfTest($probe);

        $result = $test->run($this->envPath, ['http://127.0.0.1/api/v1/health']);
        self::assertTrue($result->ok);
        self::assertArrayHasKey('http://127.0.0.1/api/v1/health', $result->httpResults);
    }
}
