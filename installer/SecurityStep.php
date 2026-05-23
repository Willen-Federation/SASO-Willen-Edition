<?php

declare(strict_types=1);

namespace saso\installer;

use saso\util\EnvLoader;
use saso\util\EnvWriter;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;

/**
 * Orchestrator for the installer's "Security" step.
 *
 * The wizard previously wrote whatever the operator typed (or whatever the UI
 * happened to pre-fill) without validating the boot-time shape of APP_KEY /
 * JWT_SECRET / WEBHOOK_SECRET. Production installs that flowed through the
 * wizard could therefore land in a state where `/api/v1/*` returned 500 with
 * SASO-INFRA-9000 ("Refusing to boot with an all-zero AES key"), forcing
 * operators to run `tools/repair-app-key.php` after the fact.
 *
 * This class closes that gap on the fresh-install path:
 *
 *   - For each of APP_KEY / JWT_SECRET / WEBHOOK_SECRET, if the operator
 *     supplied a value via the form it is validated using the same 3-shape
 *     rule {@see AppKeyResolver::tryResolve()} applies. Valid values pass
 *     through verbatim; invalid values reject the submission with a clear
 *     message.
 *   - When the form leaves a field blank, a fresh `base64_encode(random_bytes(32))`
 *     is generated.
 *   - When the existing `.env` already contains a valid value, the step is
 *     idempotent: the prior value is preserved unless the operator opts in
 *     to a regeneration via the "regenerate" flag.
 *   - All three writes happen under a single read-modify-write window. If
 *     any individual write fails we roll back the `.env` to its prior state
 *     (or delete it entirely when it didn't exist before this run).
 *   - After writing, each value is verified through {@see AppKeyResolver}
 *     to assert that the request that boots the API right after the wizard
 *     finishes will not crash on SASO-INFRA-9000.
 *
 * The class is deliberately independent of the View / request layer so the
 * unit tests can drive it against a tmpdir without spinning up the wizard.
 */
final class SecurityStep
{
    public const KEY_APP         = 'APP_KEY';
    public const KEY_JWT         = 'JWT_SECRET';
    public const KEY_WEBHOOK     = 'WEBHOOK_SECRET';

    /** @var list<string> */
    public const SECRET_KEYS = [self::KEY_APP, self::KEY_JWT, self::KEY_WEBHOOK];

    /** @var callable(string, string, string): void */
    private $writeFn;

    /**
     * @param (callable(string, string, string): void)|EnvWriter|null $writer
     *        Either an {@see EnvWriter} instance (production path) or a
     *        custom callable `(key, value, envPath) => void` for tests that
     *        want to simulate write failures. Defaults to a fresh EnvWriter.
     */
    public function __construct(EnvWriter|callable|null $writer = null)
    {
        if ($writer === null) {
            $writer = new EnvWriter();
        }
        if ($writer instanceof EnvWriter) {
            $this->writeFn = static function (string $k, string $v, string $p) use ($writer): void {
                $writer->setOrUpdate($k, $v, $p);
            };
        } else {
            $this->writeFn = $writer;
        }
    }

    /**
     * Apply the security step against the given `.env` path.
     *
     * @param array<string, string> $input  Raw form input keyed by env name
     *                                      (`APP_KEY`, `JWT_SECRET`,
     *                                      `WEBHOOK_SECRET`). Missing keys
     *                                      are treated as blank.
     * @param bool                  $regenerate When true, valid existing
     *                                      values are still replaced.
     *                                      When false (default), the step
     *                                      is idempotent.
     *
     * @return SecurityStepResult Outcome with status, per-key decisions,
     *                            and an optional rollback diagnostic.
     */
    public function apply(string $envPath, array $input, bool $regenerate = false): SecurityStepResult
    {
        // Snapshot the prior state for rollback. We capture both whether the
        // file existed and the raw bytes, so a partial write can be undone
        // either by restoring the previous contents or deleting the freshly
        // created file.
        $envExistedBefore = is_file($envPath);
        $snapshot         = $envExistedBefore ? @file_get_contents($envPath) : null;
        if ($snapshot === false) {
            $snapshot = null;
        }

        $current   = $envExistedBefore ? EnvLoader::loadFile($envPath) : [];
        $decisions = [];
        $errors    = [];
        $writes    = [];

        foreach (self::SECRET_KEYS as $key) {
            $provided = trim((string) ($input[$key] ?? ''));
            $existing = trim((string) ($current[$key] ?? ''));

            if ($provided !== '') {
                if (!self::validate($key, $provided)) {
                    $errors[$key] = sprintf(
                        '%s に指定された値が無効です。base64 32 バイト・hex 32 バイト・32 文字以上の文字列のいずれかを指定してください。',
                        $key
                    );
                    continue;
                }
                $decisions[$key] = $existing === $provided
                    ? 'preserved'
                    : 'provided';
                $writes[$key]    = $provided;
                continue;
            }

            // No operator input — preserve a valid existing value unless the
            // caller asked us to regenerate everything.
            if (!$regenerate && self::validate($key, $existing)) {
                $decisions[$key] = 'preserved';
                continue;
            }

            $writes[$key]    = self::generateSecret();
            $decisions[$key] = 'generated';
        }

        if (!empty($errors)) {
            return SecurityStepResult::invalid($errors);
        }

        if (empty($writes)) {
            // Idempotent re-submission: nothing to write, everything already
            // valid. The wizard can advance straight away.
            return SecurityStepResult::ok($decisions);
        }

        // Atomic, per-key write with rollback on first failure. EnvWriter
        // already serialises individual writes via .env.lock, but we must
        // rebuild the prior state ourselves if one of the writes throws
        // partway through.
        foreach ($writes as $envKey => $value) {
            try {
                ($this->writeFn)($envKey, $value, $envPath);
            } catch (\Throwable $e) {
                $this->rollback($envPath, $snapshot, $envExistedBefore);
                return SecurityStepResult::writeFailed($envKey, $e->getMessage());
            }
        }

        // Post-write self-test: re-read every key we just wrote and confirm
        // AppKeyResolver / shape validators still accept it. If any fails we
        // roll back to the snapshot so the next wizard visit does not see a
        // half-broken file.
        $reread = EnvLoader::loadFile($envPath);
        foreach (self::SECRET_KEYS as $envKey) {
            $value = trim((string) ($reread[$envKey] ?? ''));
            if (!self::validate($envKey, $value)) {
                $this->rollback($envPath, $snapshot, $envExistedBefore);
                return SecurityStepResult::selfTestFailed($envKey);
            }
        }

        // Boot-time self-test using the canonical resolver. Done last because
        // it is the most expensive check and we want the cheaper shape check
        // to fail fast.
        $appKey = trim((string) ($reread[self::KEY_APP] ?? ''));
        if (AppKeyResolver::tryResolve($appKey) === null) {
            $this->rollback($envPath, $snapshot, $envExistedBefore);
            return SecurityStepResult::selfTestFailed(self::KEY_APP);
        }

        return SecurityStepResult::ok($decisions);
    }

    /**
     * Validate a candidate secret against the boot-time rules.
     *
     * APP_KEY / JWT_SECRET accept base64-32B, hex-32B, or any ≥ 32 char string
     * (matches `AppKeyResolver::tryResolve()`). WEBHOOK_SECRET only requires
     * ≥ 32 chars (it is used as a literal token compare, not as an AES key).
     */
    public static function validate(string $envKey, string $value): bool
    {
        if ($value === '') {
            return false;
        }
        if ($envKey === self::KEY_WEBHOOK) {
            return strlen($value) >= 32;
        }
        return AppKeyResolver::tryResolve($value) !== null;
    }

    /**
     * Generate a fresh random secret. Matches the shape `repair-app-key.php`
     * uses (`base64_encode(random_bytes(32))`) so operators reading both
     * `.env` and the runbook see the same encoding.
     */
    public static function generateSecret(): string
    {
        return base64_encode(random_bytes(32));
    }

    private function rollback(string $envPath, ?string $snapshot, bool $envExistedBefore): void
    {
        if (!$envExistedBefore) {
            if (is_file($envPath)) {
                @unlink($envPath);
            }
            return;
        }
        if ($snapshot === null) {
            // We could not read the prior file even though it existed — leave
            // whatever EnvWriter produced in place so an operator can recover
            // by hand. Better than truncating the file to empty.
            return;
        }
        @file_put_contents($envPath, $snapshot, LOCK_EX);
        @chmod($envPath, 0600);
    }
}
