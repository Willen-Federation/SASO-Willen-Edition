<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\Mcp\McpTool;

final class ListFeatureFlagsTool implements McpTool
{
    public function __construct(private readonly FeatureFlagRepository $flags)
    {
    }

    public function name(): string
    {
        return 'list_feature_flags';
    }

    public function description(): string
    {
        return 'List every registered feature flag with its enabled state, rollout %, and auto-disable status.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $rows = $this->flags->listAll();
        return [
            'flags' => array_map(
                static fn (\Saso\Domain\Feature\FeatureFlag $f): array => [
                    'key'             => $f->key->value,
                    'description'     => $f->description,
                    'enabled'         => $f->enabled,
                    'rolloutPercent'  => $f->rolloutPercent,
                    'autoDisabledAt'  => $f->autoDisabledAt?->format(DATE_ATOM),
                ],
                $rows,
            ),
        ];
    }

    public function requiredScope(): ?string
    {
        return 'feature_flags:read';
    }
}
