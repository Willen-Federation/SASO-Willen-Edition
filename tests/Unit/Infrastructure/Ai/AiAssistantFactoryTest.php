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

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    /** Keys that must be isolated across every test in this suite. */
    private const ENV_KEYS = [
        'AI_PROVIDER',
        'GEMINI_API_KEY',
        'LOCAL_GEMINI_KEY',
        'OPENAI_API_KEY',
        'ANTHROPIC_API_KEY',
    ];

    protected function setUp(): void
    {
        // Snapshot and unset all relevant env vars so tests are fully isolated.
        // docker-compose.yml injects real API keys (e.g. LOCAL_GEMINI_KEY) into
        // the container process; without this, leftover values survive across
        // tests even after putenv("KEY=") because on some PHP/OS combinations
        // getenv() returns the original process-env value instead of the
        // overriding empty string.
        foreach (self::ENV_KEYS as $key) {
            $this->savedEnv[$key] = getenv($key);
            putenv($key); // unset — getenv() will return false
        }

        $this->settingService = $this->createMock(SystemSettingService::class);
    }

    protected function tearDown(): void
    {
        // Restore the original process environment so other test classes are
        // not affected by env changes made inside individual test methods.
        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("$key=$value");
            }
        }
    }

    public function testForVisionResolvesProviderFromEnvironment(): void
    {
        // AI_PROVIDER=gemini, but no API key configured → NullAssistant
        putenv('AI_PROVIDER=gemini');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(NullAssistant::class, $assistant);
    }

    public function testForVisionResolvesGeminiKeyFromLocalGeminiKey(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('LOCAL_GEMINI_KEY=test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
    }

    public function testForVisionReturnsNullAssistantWhenNoKeysConfigured(): void
    {
        putenv('AI_PROVIDER=gemini');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(NullAssistant::class, $assistant);
    }

    public function testForVisionResolvesOpenAiKeys(): void
    {
        putenv('AI_PROVIDER=openai');
        putenv('OPENAI_API_KEY=sk-test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(OpenAiAssistant::class, $assistant);
    }

    public function testForVisionResolvesClaudeKeys(): void
    {
        putenv('AI_PROVIDER=claude');
        putenv('ANTHROPIC_API_KEY=sk-ant-test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(ClaudeAssistant::class, $assistant);
    }

    public function testForChatResolvesProviderFromEnvironment(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('LOCAL_GEMINI_KEY=test-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forChat($this->settingService);

        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
    }

    public function testPreferencesEnvironmentVarOverDatabase(): void
    {
        putenv('AI_PROVIDER=gemini');
        putenv('LOCAL_GEMINI_KEY=env-key');

        // Even if database returns a different provider, env takes precedence.
        $dbSetting = SettingValue::string('openai');
        $this->settingService->method('get')->willReturnMap([
            [new SettingKey('ai.provider_vision'), $dbSetting],
        ]);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
    }

    public function testHandlesMultipleApiKeys(): void
    {
        putenv('AI_PROVIDER=gemini');
        // No env keys; setUp() already unset them — database keys take precedence.

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

        $this->assertInstanceOf(FallbackChainAssistant::class, $assistant);
    }

    public function testGeminiKeyLookupChain(): void
    {
        // GEMINI_API_KEY takes precedence over LOCAL_GEMINI_KEY
        putenv('AI_PROVIDER=gemini');
        putenv('GEMINI_API_KEY=prod-key');
        putenv('LOCAL_GEMINI_KEY=dev-key');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(GeminiAssistant::class, $assistant);
    }

    public function testHandlesEmptyKeyArrays(): void
    {
        putenv('AI_PROVIDER=gemini');
        // No env keys; setUp() already unset them. DB returns empty strings.

        $dbSetting = SettingValue::json(['', '']);

        $this->settingService->method('get')->willReturnMap([
            [new SettingKey('ai.gemini_api_keys'), $dbSetting],
        ]);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        // Empty strings are filtered out → NullAssistant
        $this->assertInstanceOf(NullAssistant::class, $assistant);
    }

    public function testInvalidProviderReturnsNullAssistant(): void
    {
        putenv('AI_PROVIDER=invalid-provider');
        $this->settingService->method('get')->willReturn(null);

        $assistant = AiAssistantFactory::forVision($this->settingService);

        $this->assertInstanceOf(NullAssistant::class, $assistant);
    }
}
