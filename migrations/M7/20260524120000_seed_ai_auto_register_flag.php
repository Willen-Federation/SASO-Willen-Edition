<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Seeds the `ai.auto_register` feature flag — operator-managed gate that
 * controls the AI Auto-Registration mode.
 *
 * When enabled, drafts uploaded via POST /api/v1/items/auto-register (or
 * marked auto_register=1) are promoted directly to `item` rows after
 * enrichment, skipping the manual confirmation step. When disabled, the
 * upload silently falls back to the legacy draft workflow so users can
 * still complete registration manually.
 *
 * Unlike `ai.auto_judge`, this flag is manually toggled by admins — it has
 * no auto-sync behaviour.
 */
final class SeedAiAutoRegisterFlag extends AbstractMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->execute(
            "INSERT INTO feature_flag
            (key_name, description, enabled, rollout_percent, conditions, error_threshold, error_window_min, created_at, updated_at)
            VALUES (
                'ai.auto_register',
                'Auto-register drafts directly into the item table after AI enrichment, skipping manual confirmation. Disable to fall back to the legacy draft confirmation flow.',
                0,
                0,
                NULL,
                5,
                60,
                '{$now}',
                '{$now}'
            )"
        );
    }

    public function down(): void
    {
        $this->execute("DELETE FROM feature_flag WHERE key_name = 'ai.auto_register'");
    }
}
