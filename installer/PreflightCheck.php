<?php

declare(strict_types=1);

namespace saso\installer;

/**
 * Single preflight assertion. Carries enough context to render an actionable
 * error page when it fails — the operator should be able to copy the
 * `remedy` line straight into a shell.
 */
final class PreflightCheck
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly bool $ok,
        public readonly string $detail,
        public readonly ?string $remedy,
    ) {
    }
}
