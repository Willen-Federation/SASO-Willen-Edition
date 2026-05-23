<?php

declare(strict_types=1);

namespace saso\installer;

/**
 * Value object capturing the outcome of {@see SecurityStep::apply()}.
 *
 * Four shapes are possible:
 *
 *   - {@see ok()} — every key satisfies the boot-time rules; the wizard can
 *     advance to the next step.
 *   - {@see invalid()} — the operator supplied an unacceptable value via the
 *     form; the wizard re-renders the step with per-field error messages.
 *   - {@see writeFailed()} — `.env` write or atomic rename failed; the file
 *     has been rolled back to its prior state.
 *   - {@see selfTestFailed()} — the value was written but `AppKeyResolver`
 *     or the shape check rejected it on re-read; the file has been rolled
 *     back. This is a defensive branch — it should never fire because we
 *     validate before writing — but keeps the contract explicit if a future
 *     refactor adds a new validator stricter than the pre-write one.
 *
 * Each "decision" recorded under `decisions[]` is one of: `preserved`,
 * `provided`, `generated`. The runbook documents what each means.
 */
final class SecurityStepResult
{
    public const STATUS_OK              = 'ok';
    public const STATUS_INVALID         = 'invalid';
    public const STATUS_WRITE_FAILED    = 'write_failed';
    public const STATUS_SELFTEST_FAILED = 'selftest_failed';

    /**
     * @param array<string, string>      $decisions
     * @param array<string, string>      $errors
     */
    private function __construct(
        public readonly string $status,
        public readonly array $decisions,
        public readonly array $errors,
        public readonly ?string $failedKey,
        public readonly ?string $detail,
    ) {
    }

    /** @param array<string, string> $decisions */
    public static function ok(array $decisions): self
    {
        return new self(self::STATUS_OK, $decisions, [], null, null);
    }

    /** @param array<string, string> $errors */
    public static function invalid(array $errors): self
    {
        return new self(self::STATUS_INVALID, [], $errors, null, null);
    }

    public static function writeFailed(string $key, string $detail): self
    {
        return new self(self::STATUS_WRITE_FAILED, [], [], $key, $detail);
    }

    public static function selfTestFailed(string $key): self
    {
        return new self(self::STATUS_SELFTEST_FAILED, [], [], $key, null);
    }

    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }
}
