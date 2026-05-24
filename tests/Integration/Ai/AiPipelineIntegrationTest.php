<?php

declare(strict_types=1);

namespace Saso\Tests\Integration\Ai;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\Step\AiVisionStep;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Infrastructure\Ai\AiAssistantFactory;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use saso\repository\DBConnection;

final class AiPipelineIntegrationTest extends TestCase
{
    private SystemSettingService $settingService;

    protected function setUp(): void
    {
        // Skip integration tests if no Gemini key is configured
        $geminiKey = getenv('LOCAL_GEMINI_KEY') ?: getenv('GEMINI_API_KEY');
        if (!$geminiKey) {
            $this->markTestSkipped('No Gemini API key configured (set LOCAL_GEMINI_KEY or GEMINI_API_KEY)');
        }

        try {
            $pdo = DBConnection::pdo();
            $appKey = (string) (getenv('APP_KEY') ?: '');
            if ($appKey !== '') {
                $rawKey = base64_decode($appKey, true);
                if ($rawKey !== false && strlen($rawKey) === 32) {
                    $encryptor = new SecretEncryptor($rawKey);
                    $this->settingService = new PdoSystemSettingService($pdo, $encryptor);
                } else {
                    $this->markTestSkipped('APP_KEY is not properly configured');
                }
            } else {
                $this->markTestSkipped('APP_KEY is not configured');
            }
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database is not available: '.$e->getMessage());
        }
    }

    public function testAiVisionStepProcessesTextInput(): void
    {
        putenv('AI_PROVIDER=gemini');

        $aiAssistant = AiAssistantFactory::forVision($this->settingService);
        $aiVisionStep = new AiVisionStep($aiAssistant, $this->enabledAiFlagRepo());

        $result = $aiVisionStep->run('', 'What is 2 + 2?', []);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testAiVisionStepProcessesImageInput(): void
    {
        putenv('AI_PROVIDER=gemini');

        // Create a simple test image (1x1 transparent PNG)
        $pngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );
        $tmpFile = tempnam(sys_get_temp_dir(), 'ai_test_');
        if ($tmpFile === false) {
            $this->markTestSkipped('Could not create temporary file for image');
        }

        file_put_contents($tmpFile, $pngData);

        try {
            $aiAssistant = AiAssistantFactory::forVision($this->settingService);
            $aiVisionStep = new AiVisionStep($aiAssistant, $this->enabledAiFlagRepo());

            $result = $aiVisionStep->run($tmpFile, 'Describe what you see in this image', []);

            $this->assertIsArray($result);
            $this->assertNotEmpty($result);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testAiVisionStepHandlesInvalidImageGracefully(): void
    {
        putenv('AI_PROVIDER=gemini');

        $tmpFile = tempnam(sys_get_temp_dir(), 'ai_invalid_');
        if ($tmpFile === false) {
            $this->markTestSkipped('Could not create temporary file for invalid image');
        }

        // Write invalid image data
        file_put_contents($tmpFile, 'not a real image');

        try {
            $aiAssistant = AiAssistantFactory::forVision($this->settingService);
            $aiVisionStep = new AiVisionStep($aiAssistant, $this->enabledAiFlagRepo());

            // This should either return an error or handle it gracefully
            $result = $aiVisionStep->run($tmpFile, 'Describe this', []);

            // We expect either an error array or a graceful response
            $this->assertIsArray($result);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testAiProviderResolutionFromEnvironment(): void
    {
        putenv('AI_PROVIDER=gemini');

        $aiAssistant = AiAssistantFactory::forVision($this->settingService);

        // Verify we got a real assistant, not NullAssistant
        $this->assertNotNull($aiAssistant);
        $this->assertNotEquals('Saso\Infrastructure\Ai\NullAssistant', get_class($aiAssistant));
    }

    private function enabledAiFlagRepo(): FeatureFlagRepository
    {
        return new class () implements FeatureFlagRepository {
            public function findByKey(FeatureKey $key): ?FeatureFlag
            {
                return new FeatureFlag(
                    id: 1,
                    key: $key,
                    description: 'AI auto-judge',
                    enabled: true,
                    rolloutPercent: 100,
                    conditions: null,
                    errorThreshold: 0,
                    errorWindowMinutes: 1,
                    autoDisabledAt: null,
                    autoDisableReason: null,
                    createdAt: new DateTimeImmutable(),
                    updatedAt: new DateTimeImmutable(),
                );
            }

            public function findById(int $id): ?FeatureFlag
            {
                return null;
            }

            /** @return list<FeatureFlag> */
            public function listAll(): array
            {
                return [];
            }

            public function nextId(): int
            {
                return 1;
            }

            public function save(FeatureFlag $flag): FeatureFlag
            {
                return $flag;
            }

            public function delete(int $id): void
            {
            }
        };
    }
}
