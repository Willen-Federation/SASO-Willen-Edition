<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use saso\installer\SecurityStep;
use saso\installer\SecurityStepResult;
use saso\util\EnvLoader;
use saso\util\EnvWriter;

/**
 * Behavioural contract for the installer's security step.
 *
 * Covers the four guarantees PR-A2 introduces:
 *   1. fresh secrets are generated when the operator submits blanks
 *   2. operator-supplied values are accepted when they pass the boot rules
 *   3. invalid operator-supplied values are rejected with structured errors
 *   4. existing valid values are preserved unless `regenerate` is set
 *   5. a partial write rolls `.env` back to its prior bytes
 *
 * Every test runs against an isolated tmpdir so PHP's putenv() / global env
 * state is never mutated by the suite.
 */
#[CoversClass(SecurityStep::class)]
#[CoversClass(SecurityStepResult::class)]
final class SecurityStepTest extends TestCase
{
    private string $tmpDir;
    private string $envPath;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir().'/saso-secstep-'.uniqid('', true);
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

    public function testGeneratesAllThreeSecretsWhenNoneProvided(): void
    {
        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertTrue($result->isOk(), 'Expected ok result, got status='.$result->status);
        self::assertSame('generated', $result->decisions[SecurityStep::KEY_APP] ?? null);
        self::assertSame('generated', $result->decisions[SecurityStep::KEY_JWT] ?? null);
        self::assertSame('generated', $result->decisions[SecurityStep::KEY_WEBHOOK] ?? null);

        $env = EnvLoader::loadFile($this->envPath);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]{44}$/', $env[SecurityStep::KEY_APP] ?? '');
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]{44}$/', $env[SecurityStep::KEY_JWT] ?? '');
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]{44}$/', $env[SecurityStep::KEY_WEBHOOK] ?? '');
    }

    public function testAcceptsAdminProvidedBase64Secret(): void
    {
        $custom = base64_encode(random_bytes(32));

        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => $custom,
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertTrue($result->isOk());
        self::assertSame('provided', $result->decisions[SecurityStep::KEY_APP] ?? null);

        $env = EnvLoader::loadFile($this->envPath);
        self::assertSame($custom, $env[SecurityStep::KEY_APP] ?? null);
    }

    public function testAcceptsAdminProvidedHexSecret(): void
    {
        $custom = bin2hex(random_bytes(32)); // 64 hex chars

        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => $custom,
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertTrue($result->isOk());
        $env = EnvLoader::loadFile($this->envPath);
        self::assertSame($custom, $env[SecurityStep::KEY_APP] ?? null);
    }

    public function testRejectsInvalidProvidedAppKey(): void
    {
        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => 'too-short',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertFalse($result->isOk());
        self::assertSame(SecurityStepResult::STATUS_INVALID, $result->status);
        self::assertArrayHasKey(SecurityStep::KEY_APP, $result->errors);
        self::assertFileDoesNotExist($this->envPath, 'No partial .env should be created on invalid input.');
    }

    public function testRejectsInvalidWebhookSecret(): void
    {
        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => 'short',
        ]);

        self::assertSame(SecurityStepResult::STATUS_INVALID, $result->status);
        self::assertArrayHasKey(SecurityStep::KEY_WEBHOOK, $result->errors);
    }

    public function testIdempotentWhenAllValuesAlreadyValid(): void
    {
        $appKey  = base64_encode(random_bytes(32));
        $jwt     = base64_encode(random_bytes(32));
        $webhook = bin2hex(random_bytes(32));
        file_put_contents(
            $this->envPath,
            "APP_KEY=$appKey\nJWT_SECRET=$jwt\nWEBHOOK_SECRET=$webhook\n"
        );
        $before = file_get_contents($this->envPath);

        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertTrue($result->isOk());
        self::assertSame('preserved', $result->decisions[SecurityStep::KEY_APP] ?? null);
        self::assertSame('preserved', $result->decisions[SecurityStep::KEY_JWT] ?? null);
        self::assertSame('preserved', $result->decisions[SecurityStep::KEY_WEBHOOK] ?? null);
        self::assertSame($before, file_get_contents($this->envPath), '.env bytes should not change on idempotent re-submit.');
    }

    public function testRegenerateFlagReplacesValidValues(): void
    {
        $appKey  = base64_encode(random_bytes(32));
        $jwt     = base64_encode(random_bytes(32));
        $webhook = bin2hex(random_bytes(32));
        file_put_contents(
            $this->envPath,
            "APP_KEY=$appKey\nJWT_SECRET=$jwt\nWEBHOOK_SECRET=$webhook\n"
        );

        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ], regenerate: true);

        self::assertTrue($result->isOk());
        self::assertSame('generated', $result->decisions[SecurityStep::KEY_APP] ?? null);
        $env = EnvLoader::loadFile($this->envPath);
        self::assertNotSame($appKey, $env[SecurityStep::KEY_APP] ?? null, 'APP_KEY should have rotated under regenerate flag.');
    }

    public function testProvidedValueOverridesExisting(): void
    {
        $appKey = base64_encode(random_bytes(32));
        file_put_contents($this->envPath, "APP_KEY=$appKey\n");

        $newKey = base64_encode(random_bytes(32));
        $step   = new SecurityStep();
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => $newKey,
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertTrue($result->isOk());
        self::assertSame('provided', $result->decisions[SecurityStep::KEY_APP] ?? null);
        $env = EnvLoader::loadFile($this->envPath);
        self::assertSame($newKey, $env[SecurityStep::KEY_APP] ?? null);
    }

    public function testRollbackRestoresPriorEnvOnWriteFailure(): void
    {
        // Pre-seed a valid APP_KEY so we can observe rollback restoring it.
        $appKey = base64_encode(random_bytes(32));
        file_put_contents($this->envPath, "APP_KEY=$appKey\n");
        $before = file_get_contents($this->envPath);

        // Custom writer callable that succeeds on the first call and throws
        // on the second — the failure is in the middle of the three-key
        // write batch. EnvWriter is final so we cannot subclass it; the
        // callable injection point exists exactly for this scenario.
        $realWriter = new EnvWriter();
        $callCount  = 0;
        $writer     = static function (string $key, string $value, string $envPath) use ($realWriter, &$callCount): void {
            $callCount++;
            if ($callCount === 1) {
                $realWriter->setOrUpdate($key, $value, $envPath);
                return;
            }
            throw new RuntimeException('Simulated write failure on key '.$key);
        };

        $step   = new SecurityStep($writer);
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ], regenerate: true);

        self::assertFalse($result->isOk());
        self::assertSame(SecurityStepResult::STATUS_WRITE_FAILED, $result->status);
        self::assertSame(
            $before,
            file_get_contents($this->envPath),
            'Rollback should restore the prior .env contents byte-for-byte.'
        );
    }

    public function testRollbackDeletesEnvWhenItDidNotExistBefore(): void
    {
        self::assertFileDoesNotExist($this->envPath);

        $writer = static function (string $key, string $value, string $envPath): void {
            // Simulate the failure happening on the FIRST write — the
            // EnvWriter parent may have auto-seeded the file from
            // .env.example before throwing, so rollback must clean it up.
            @file_put_contents($envPath, "# partial\n");
            throw new RuntimeException('Simulated write failure on '.$key);
        };

        $step   = new SecurityStep($writer);
        $result = $step->apply($this->envPath, [
            SecurityStep::KEY_APP     => '',
            SecurityStep::KEY_JWT     => '',
            SecurityStep::KEY_WEBHOOK => '',
        ]);

        self::assertSame(SecurityStepResult::STATUS_WRITE_FAILED, $result->status);
        self::assertFileDoesNotExist($this->envPath, 'Rollback should remove a freshly-created .env.');
    }

    public function testStaticValidateAcceptsAllThreeShapes(): void
    {
        $b64 = base64_encode(random_bytes(32));
        $hex = bin2hex(random_bytes(32));
        $pwd = str_repeat('p', 32);

        self::assertTrue(SecurityStep::validate(SecurityStep::KEY_APP, $b64));
        self::assertTrue(SecurityStep::validate(SecurityStep::KEY_APP, $hex));
        self::assertTrue(SecurityStep::validate(SecurityStep::KEY_APP, $pwd));
        self::assertFalse(SecurityStep::validate(SecurityStep::KEY_APP, 'tooShort'));

        // Webhook only requires ≥ 32 chars.
        self::assertTrue(SecurityStep::validate(SecurityStep::KEY_WEBHOOK, $pwd));
        self::assertFalse(SecurityStep::validate(SecurityStep::KEY_WEBHOOK, 'short'));
    }

    public function testGeneratedSecretIsBase64Of32Bytes(): void
    {
        $secret = SecurityStep::generateSecret();
        self::assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]{44}$/', $secret);

        $raw = base64_decode($secret, true);
        self::assertNotFalse($raw);
        self::assertSame(32, strlen($raw));
    }
}
