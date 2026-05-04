<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Ai;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Infrastructure\Ai\AiAssistantFactory;
use Saso\Infrastructure\Ai\ClaudeAssistant;
use Saso\Infrastructure\Ai\FallbackChainAssistant;
use Saso\Infrastructure\Ai\GeminiAssistant;
use Saso\Infrastructure\Ai\NullAssistant;
use Saso\Infrastructure\Ai\OpenAiAssistant;

final class AiAssistantFactoryTest extends TestCase
{
    private SystemSettingService $settingService;

    protected function setUp(): void
    {
        // Create a mock setting service
        $this->settingService = $this->createMock(SystemSettingService::class);
    }

    public function testForVisionResolvesProviderFromEnvironment(): void
    {
        putenv('AI_PROVIDER=gemini');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(NullAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
    }

    public function testForVisionResolvesGeminiKeyFromLocalGeminiKey(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('LOCAL_GEMINI_KEY=test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
        putenv('LOCAL_GEMINI_KEY=');
    }

    public function testForVisionReturnsNullAssistantWhenNoKeysConfigured(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('GEMINI_API_KEY=');
        putenv('LOCAL_GEMINI_KEY=');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(NullAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
    }

    public function testForVisionResolvesOpenAiKeys(): void
    {
        putenv('AI_PROVIDER=openai');
        putenv('OPENAI_API_KEY=sk-test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(OpenAiAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
        putenv('OPENAI_API_KEY=');
    }

    public function testForVisionResolvesClaudeKeys(): void
    {
        putenv('AI_PROVIDER=claude');
        putenv('ANTHROPIC_API_KEY=sk-ant-test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(ClaudeAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
        putenv('ANTHROPIC_API_KEY=');
    }

    public function testForChatResolvesProviderFromEnvironment(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('LOCAL_GEMINI_KEY=test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forChat($this->settingService);

        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
        putenv('LOCAL_GEMINI_KEY=');
    }

    public function testPreferencesEnvironmentVarOverDatabase(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('LOCAL_GEMINI_KEY=env-key');

        // Even if database returns a different provider
        $dbSetting = SettingValue::string('openai');
        $this->settingService->method('get')->willReturnMap([
            [new SettingKey('ai.provider_vision'), $dbSetting],
        ]);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        // Should use gemini from environment, not openai from database
        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
        putenv('LOCAL_GEMINI_KEY=');
    }

    public function testHandlesMultipleApiKeys(): void
    {
        putenv('AI_PROVIDER=gemini');
        // Clear environment keys so database keys take precedence
        putenv('GEMINI_API_KEY=');
        putenv('LOCAL_GEMINI_KEY=');

        $dbSetting = SettingValue::json(['key1', 'key2', 'key3']);

        $this->settingService->method('get')->willReturnCallback(
            function (SettingKey $key) use ($dbSetting) {
                if ($key->value === 'ai.gemini_api_keys') {
                    return $dbSetting;
                }
                return null;
            }
        );

        $assistant = AiAssistantFactory::forVision($this->settingService);

        // Multiple keys should create a FallbackChainAssistant
        $this->assertInstanceOf(FallbackChainAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
    }

    public function testGeminiKeyLookupChain(): void
    {
        // Test the precedence: GEMINI_API_KEY > LOCAL_GEMINI_KEY > database

        // Test 1: GEMINI_API_KEY takes precedence
        putenv('AI_PROVIDER=gemini');
        putenv('GEMINI_API_KEY=prod-key');
        putenv('LOCAL_GEMINI_KEY=dev-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);
        $this->assertInstanceOf(GeminiAssistant::class, $assistant);

        // Clean up
        putenv('AI_PROVIDER=');
        putenv('GEMINI_API_KEY=');
        putenv('LOCAL_GEMINI_KEY=');
    }

    public function testHandlesEmptyKeyArrays(): void
    {
        putenv('AI_PROVIDER=gemini');
        // Clear environment keys so database keys take precedence
        putenv('GEMINI_API_KEY=');
        putenv('LOCAL_GEMINI_KEY=');

        $dbSetting = SettingValue::json(['', '']);

        $this->settingService->method('get')->willReturnMap([
            [new SettingKey('ai.gemini_api_keys'), $dbSetting],
        ]);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        // Empty strings should be filtered out, resulting in NullAssistant
        $this->assertInstanceOf(NullAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
    }

    public function testInvalidProviderReturnsNullAssistant(): void
    {
        putenv('AI_PROVIDER=invalid-provider');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(NullAssistant::class, $assistant);
        putenv('AI_PROVIDER=');
    }
}
