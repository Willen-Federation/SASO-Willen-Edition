<?php
namespace saso\debug;

use InvalidArgumentException;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Enrichment\Step\AiVisionStep;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\Setting\SettingKey;
use Saso\Infrastructure\Ai\AiAssistantFactory;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class AiDebugDIContainer implements DIContainer
{
    private const JSON_HEADER = 'Content-Type: application/json; charset=utf-8';
    private array $query = [];
    private array $post = [];

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->query = $query;
        $this->post = $post;
    }

    public function flow(): View
    {
        // Only allow in debug mode
        if (!$this->isDebugMode()) {
            http_response_code(403);
            header(self::JSON_HEADER);
            echo json_encode(['error' => 'Debug endpoints are not available in production.']);
            exit;
        }

        $pdo = DBConnection::pdo();
        $appKey = (string) (getenv('APP_KEY') ?: '');
        if ($appKey === '') {
            throw new InvalidArgumentException('APP_KEY is not configured; cannot initialize SecretEncryptor.');
        }
        $rawKey = base64_decode($appKey, true);
        if ($rawKey === false || strlen($rawKey) !== 32) {
            throw new InvalidArgumentException('APP_KEY must be a base64-encoded 32-byte value.');
        }
        $encryptor = new SecretEncryptor($rawKey);
        $settingService = new PdoSystemSettingService($pdo, $encryptor);

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            return $this->statusAction($settingService);
        }

        if ($method === 'POST') {
            return $this->probeAction($settingService);
        }

        http_response_code(405);
        header('Allow: GET, POST');
        header(self::JSON_HEADER);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    private function statusAction(PdoSystemSettingService $settingService): View
    {
        $envProvider = getenv('AI_PROVIDER');
        $providerVisionValue = $settingService->get(new SettingKey('ai.provider_vision'));
        $providerVision = $envProvider ?: ($providerVisionValue?->asString() ?? 'null');
        $providerChatValue = $settingService->get(new SettingKey('ai.provider_chat'));
        $providerChat = $providerChatValue?->asString() ?? 'null';

        $isConfigured = ($providerVision !== 'null' && $providerVision !== null);
        $keysConfigured = false;

        if ($isConfigured && is_string($providerVision)) {
            $keysConfigured = $this->checkKeysConfigured($providerVision, $settingService);
        }

        header(self::JSON_HEADER);
        echo json_encode([
            'provider_vision' => $providerVision,
            'provider_chat' => $providerChat,
            'keys_configured' => $keysConfigured,
            'assistant_class' => $isConfigured && $keysConfigured ? ucfirst($providerVision) . 'Assistant' : 'NullAssistant',
            'env_override' => $envProvider ?: null,
        ]);
        exit;
    }

    private function checkKeysConfigured(string $provider, PdoSystemSettingService $settingService): bool
    {
        // Check environment variables first (matches AiAssistantFactory logic)
        $envVar = match ($provider) {
            'openai' => 'OPENAI_API_KEY',
            'gemini' => 'GEMINI_API_KEY',
            'anthropic' => 'ANTHROPIC_API_KEY',
            default => null,
        };

        if ($envVar !== null) {
            $envValue = getenv($envVar);
            if ($envValue !== false && $envValue !== '') {
                return true;
            }

            // Check for LOCAL_* fallback for development
            if ($provider === 'gemini') {
                $localValue = getenv('LOCAL_GEMINI_KEY');
                if ($localValue !== false && $localValue !== '') {
                    return true;
                }
            }
        }

        // Fallback to database configuration
        $keysKey = match ($provider) {
            'openai' => 'ai.openai_api_keys',
            'gemini' => 'ai.gemini_api_keys',
            'anthropic' => 'ai.anthropic_api_keys',
            default => null,
        };

        if ($keysKey !== null) {
            $keysValue = $settingService->get(new SettingKey($keysKey));
            return $keysValue !== null;
        }

        return false;
    }

    private function probeAction(PdoSystemSettingService $settingService): View
    {
        $input = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            header(self::JSON_HEADER);
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        $imageBase64 = $input['image_base64'] ?? null;
        $text = $input['text'] ?? null;

        if (empty($imageBase64) && empty($text)) {
            http_response_code(400);
            header(self::JSON_HEADER);
            echo json_encode(['error' => 'Either image_base64 or text is required']);
            exit;
        }

        try {
            $aiAssistant = AiAssistantFactory::forVision($settingService);
            // Debug probe always bypasses the feature flag — it is an admin-only tool for
            // directly testing the AI pipeline regardless of flag state.
            $alwaysEnabled = new class implements FeatureFlagRepository {
                public function findByKey(FeatureKey $key): ?FeatureFlag
                {
                    $now = new \DateTimeImmutable();
                    return new FeatureFlag(1, $key, 'debug-probe', true, 100, null, 0, 1, null, null, $now, $now);
                }
                public function findById(int $id): ?FeatureFlag { return null; }
                /** @return list<FeatureFlag> */
                public function listAll(): array { return []; }
                public function save(FeatureFlag $flag): FeatureFlag { return $flag; }
                public function delete(int $id): void {}
            };
            $aiVisionStep = new AiVisionStep($aiAssistant, $alwaysEnabled);

            // If base64 image provided, decode and write to temporary file
            $imagePath = null;
            if (!empty($imageBase64)) {
                $tmpDir = sys_get_temp_dir();
                $tmpFile = tempnam($tmpDir, 'ai_probe_');
                if ($tmpFile === false) {
                    throw new \RuntimeException('Failed to create temporary file');
                }
                $decoded = base64_decode($imageBase64, true);
                if ($decoded === false) {
                    throw new \RuntimeException('Invalid base64 encoding');
                }
                file_put_contents($tmpFile, $decoded);
                $imagePath = $tmpFile;
            }

            // Run AI vision step
            $result = $aiVisionStep->run($imagePath ?? '', $text ?? null, []);

            // Clean up temp file
            if ($imagePath !== null && file_exists($imagePath)) {
                unlink($imagePath);
            }

            http_response_code(200);
            header(self::JSON_HEADER);
            echo json_encode([
                'success' => true,
                'result' => $result,
            ]);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            header(self::JSON_HEADER);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            exit;
        }
    }

    private function isDebugMode(): bool
    {
        // Check APP_DEBUG environment variable
        $appDebug = getenv('APP_DEBUG');
        if ($appDebug === 'true' || $appDebug === '1') {
            return true;
        }

        // Check if .ENV file exists (local development indicator)
        if (file_exists(__DIR__ . '/../.ENV')) {
            return true;
        }

        return false;
    }
}
