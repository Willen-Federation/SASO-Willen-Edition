<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `update_feature_flag`
 *
 * Toggles `enabled` on a feature flag identified by key. Rollout percent
 * and conditions JSON are deliberately not editable through this tool —
 * those changes go through the web admin UI where they are audited under
 * a member id rather than a device token.
 *
 * Scope: `feature_flags:write`.
 */
final class UpdateFeatureFlagTool implements McpTool
{
    public function __construct(private readonly FeatureFlagRepository $flags)
    {
    }

    public function name(): string
    {
        return 'update_feature_flag';
    }

    public function description(): string
    {
        return 'Toggle a feature flag on or off by key. Use the web UI to edit rollout %, conditions, and breaker policy.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['key', 'enabled'],
            'properties' => [
                'key'     => ['type' => 'string', 'minLength' => 1, 'maxLength' => 120],
                'enabled' => ['type' => 'boolean'],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $key     = (string) ($input['key']     ?? '');
        $enabled = (bool) ($input['enabled'] ?? false);

        $flag = $this->flags->findByKey(new FeatureKey($key));
        if ($flag === null) {
            return ['ok' => false, 'reason' => 'unknown_key'];
        }
        $updated = $this->flags->save($flag->withEnabled($enabled));

        return [
            'ok'       => true,
            'key'      => $updated->key->value,
            'enabled'  => $updated->enabled,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'feature_flags:write';
    }
}
