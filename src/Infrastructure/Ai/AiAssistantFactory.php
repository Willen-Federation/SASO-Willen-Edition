<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Ai;

use OpenAI;
use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SystemSettingService;

final class AiAssistantFactory
{
    public static function forVision(SystemSettingService $settings): AiAssistant
    {
        $provider = self::resolveProvider('ai.provider_vision', $settings);

        return self::buildForProvider($provider, $settings);
    }

    public static function forChat(SystemSettingService $settings): AiAssistant
    {
        $provider = self::resolveProvider('ai.provider_chat', $settings);

        return self::buildForProvider($provider, $settings);
    }

    private static function resolveProvider(string $settingKey, SystemSettingService $settings): ?string
    {
        $envOverride = getenv('AI_PROVIDER');
        if ($envOverride !== false && $envOverride !== '') {
            return $envOverride;
        }

        $value = $settings->get(new SettingKey($settingKey));

        return $value !== null ? $value->asString() : null;
    }

    private static function buildForProvider(?string $provider, SystemSettingService $settings): AiAssistant
    {
        return match ($provider) {
            'openai' => self::buildOpenAi($settings),
            'gemini' => self::buildGemini($settings),
            'claude' => self::buildClaude($settings),
            default  => new NullAssistant(),
        };
    }

    private static function buildOpenAi(SystemSettingService $settings): AiAssistant
    {
        $keys = self::resolveKeys('ai.openai_api_keys', 'OPENAI_API_KEY', $settings);

        if ($keys === []) {
            return new NullAssistant();
        }

        if (count($keys) === 1) {
            return new OpenAiAssistant(OpenAI::client($keys[0]));
        }

        $chain = array_map(
            static fn (string $key) => new OpenAiAssistant(OpenAI::client($key)),
            $keys,
        );

        return new FallbackChainAssistant($chain);
    }

    private static function buildGemini(SystemSettingService $settings): AiAssistant
    {
        $keys = self::resolveKeys('ai.gemini_api_keys', 'GEMINI_API_KEY', $settings);

        if ($keys === []) {
            return new NullAssistant();
        }

        if (count($keys) === 1) {
            return new GeminiAssistant($keys[0]);
        }

        $chain = array_map(
            static fn (string $key) => new GeminiAssistant($key),
            $keys,
        );

        return new FallbackChainAssistant($chain);
    }

    private static function buildClaude(SystemSettingService $settings): AiAssistant
    {
        $keys = self::resolveKeys('ai.anthropic_api_keys', 'ANTHROPIC_API_KEY', $settings);

        if ($keys === []) {
            return new NullAssistant();
        }

        if (count($keys) === 1) {
            return new ClaudeAssistant($keys[0]);
        }

        $chain = array_map(
            static fn (string $key) => new ClaudeAssistant($key),
            $keys,
        );

        return new FallbackChainAssistant($chain);
    }

    /**
     * @return list<string>
     */
    private static function resolveKeys(string $settingKey, string $envVar, SystemSettingService $settings): array
    {
        $envValue = getenv($envVar);
        if ($envValue !== false && self::isUsableKey($envValue)) {
            return [$envValue];
        }

        // Fallback to LOCAL_* variant for development environments
        if ($envVar === 'GEMINI_API_KEY') {
            $localValue = getenv('LOCAL_GEMINI_KEY');
            if ($localValue !== false && self::isUsableKey($localValue)) {
                return [$localValue];
            }
        }

        $value = $settings->get(new SettingKey($settingKey));
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
