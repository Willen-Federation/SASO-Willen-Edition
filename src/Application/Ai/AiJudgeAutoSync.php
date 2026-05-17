<?php

declare(strict_types=1);

namespace Saso\Application\Ai;

use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SystemSettingService;

/**
 * Automatically synchronizes the `ai.auto_judge` feature flag state
 * based on whether an AI provider and API key are currently configured.
 *
 * Admins never manually toggle this flag. Instead, the system detects
 * the current configuration and enables the flag if and only if:
 * - A provider (openai, gemini, claude) is specified in 'ai.provider_vision'
 * - At least one API key exists for that provider
 *
 * Call this service once per request (e.g., in ProcessItemDraftHandler)
 * to ensure the flag reflects the current runtime configuration.
 */
final class AiJudgeAutoSync
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly FeatureFlagRepository $flagRepository,
    ) {
    }

    public function sync(): void
    {
        $flag = $this->flagRepository->findByKey(new FeatureKey('ai.auto_judge'));
        if ($flag === null) {
            return;
        }

        $isConfigured = $this->isAiConfigured();
        if ($flag->enabled !== $isConfigured) {
            $this->flagRepository->save($flag->withEnabled($isConfigured));
        }
    }

    private function isAiConfigured(): bool
    {
        $provider = $this->resolveProviderName();
        if ($provider === null || $provider === '') {
            return false;
        }

        $keys = $this->resolveKeys($provider);

        return count($keys) > 0;
    }

    private function resolveProviderName(): ?string
    {
        $envOverride = getenv('AI_PROVIDER');
        if ($envOverride !== false && $envOverride !== '') {
            return $envOverride;
        }

        $value = $this->settings->get(new SettingKey('ai.provider_vision'));

        return $value !== null ? $value->asString() : null;
    }

    /**
     * @return list<string>
     */
    private function resolveKeys(string $provider): array
    {
        $settingKey = match ($provider) {
            'openai' => 'ai.openai_api_keys',
            'gemini' => 'ai.gemini_api_keys',
            'claude' => 'ai.anthropic_api_keys',
            default => null,
        };

        if ($settingKey === null) {
            return [];
        }

        $envVar = match ($provider) {
            'openai' => 'OPENAI_API_KEY',
            'gemini' => 'GEMINI_API_KEY',
            'claude' => 'ANTHROPIC_API_KEY',
            default => null,
        };

        $envValue = $envVar !== null ? getenv($envVar) : false;
        if ($envValue !== false && self::isUsableKey($envValue)) {
            return [$envValue];
        }

        if ($provider === 'gemini') {
            $localValue = getenv('LOCAL_GEMINI_KEY');
            if ($localValue !== false && self::isUsableKey($localValue)) {
                return [$localValue];
            }
        }

        $value = $this->settings->get(new SettingKey($settingKey));
        if ($value === null) {
            return [];
        }

        $parsed = json_decode($value->raw, true);

        if (is_array($parsed)) {
            return array_values(array_filter($parsed, static fn (mixed $v) => is_string($v) && self::isUsableKey($v)));
        }

        $raw = $value->asString();

        return self::isUsableKey($raw) ? [$raw] : [];
    }

    private static function isUsableKey(string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }
        return !in_array($key, [
            'local-gemini-key-placeholder',
            'your-api-key',
            'your_api_key',
            'placeholder',
        ], true);
    }
}
