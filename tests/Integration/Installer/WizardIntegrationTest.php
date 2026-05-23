<?php

declare(strict_types=1);

namespace Saso\Tests\Integration\Installer;

use PHPUnit\Framework\TestCase;
use saso\installer\PostInstallSelfTest;
use saso\installer\Preflight;
use saso\installer\SecurityStep;
use saso\util\EnvLoader;

/**
 * End-to-end smoke test for the installer's new security flow.
 *
 * The wizard is exercised against a tmpdir-only `.env` so we never touch the
 * developer's real configuration. Database / admin steps are out of scope —
 * this test focuses on the chain that PR-A2 introduces:
 *
 *   Preflight::run() → SecurityStep::apply() → PostInstallSelfTest::run()
 *
 * After the chain the `.env` must contain three boot-shape-valid secrets and
 * the self-test must report ok with the (stubbed) HTTP probe returning 200.
 */
final class WizardIntegrationTest extends TestCase
{
    private string $tmpDir;
    private string $envPath;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir().'/saso-wizard-'.uniqid('', true);
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

    public function testFreshInstallChainProducesBootableEnv(): void
    {
        // 1. Preflight on a fresh tmpdir must pass.
        $preflight = Preflight::run($this->envPath);
        self::assertTrue($preflight->isOk(), 'Preflight should pass on fresh tmpdir.');

        // 2. SecurityStep with all blank input generates three secrets.
        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);
        self::assertTrue($result->isOk(), 'SecurityStep should write three fresh secrets.');

        // 3. Post-install self-test passes with a stub HTTP probe.
        $probe = static fn (string $url): array => ['ok' => true, 'status' => 200, 'error' => null];
        $test  = new PostInstallSelfTest($probe);

        $urls = [
            'http://127.0.0.1/api/v1/health',
            'http://127.0.0.1/api/v1/auth/providers',
        ];
        $self = $test->run($this->envPath, $urls);
        self::assertTrue(
            $self->ok,
            'Self-test should pass after a clean wizard run. Failures: '.json_encode($self->failures)
        );
        self::assertCount(2, $self->httpResults);

        // 4. The resulting .env contains shape-valid secrets.
        $env = EnvLoader::loadFile($this->envPath);
        foreach (SecurityStep::SECRET_KEYS as $key) {
            self::assertArrayHasKey($key, $env);
            self::assertTrue(
                SecurityStep::validate($key, (string) $env[$key]),
                "Generated $key should pass SecurityStep::validate()."
            );
        }
    }

    public function testSelfTestBlocksHttpFailure(): void
    {
        // Wizard succeeds at writing secrets, but the API is still down.
        $step = new SecurityStep();
        $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        $probe = static fn (string $url): array => ['ok' => false, 'status' => 500, 'error' => 'connection refused'];
        $test  = new PostInstallSelfTest($probe);

        $self = $test->run($this->envPath, ['http://127.0.0.1/api/v1/health']);
        self::assertFalse($self->ok, 'Self-test should fail when the API probe fails — gates installer.json deletion.');
    }
}
