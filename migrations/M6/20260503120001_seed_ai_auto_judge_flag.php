<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Seed the `ai.auto_judge` feature flag — auto-managed gate that enables
 * AI vision processing only when a provider and API key are configured.
 *
 * Admins never manually toggle this flag. Instead, the AiJudgeAutoSync service
 * automatically sets `enabled` based on whether provider+key configuration exists.
 * This migration seeds the flag in the disabled (0) state; auto-sync will
 * enable it on the first request if configuration is detected.
 */
final class SeedAiAutoJudgeFlag extends AbstractMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "INSERT INTO feature_flag
            (key_name, description, enabled, rollout_percent, conditions, error_threshold, error_window_min, created_at, updated_at)
            VALUES (
                'ai.auto_judge',
                'Auto-managed: enabled automatically when an AI provider and API key are configured. Do not toggle manually.',
                0,
                0,
                NULL,
                10,
                60,
                '{$now}',
                '{$now}'
            )"
        );
    }

    public function down(): void
    {
        $this->execute("DELETE FROM feature_flag WHERE key_name = 'ai.auto_judge'");
    }
}
