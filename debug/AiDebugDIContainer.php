<?php
namespace saso\debug;

use InvalidArgumentException;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Enrichment\Step\AiVisionStep;
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
        $providerVisionValue = $settingService->get(new SettingKey('ai.provider_vision'));
        $providerVision = $providerVisionValue?->asString() ?? 'null';
        $providerChatValue = $settingService->get(new SettingKey('ai.provider_chat'));
        $providerChat = $providerChatValue?->asString() ?? 'null';

        $isConfigured = ($providerVision !== 'null' && $providerVision !== null);
        $keysConfigured = false;

        if ($isConfigured && is_string($providerVision)) {
            $keysKey = match ($providerVision) {
                'openai' => 'ai.openai_api_keys',
                'gemini' => 'ai.gemini_api_keys',
                'anthropic' => 'ai.anthropic_api_keys',
                default => null,
            };

            if ($keysKey !== null) {
                $keysValue = $settingService->get(new SettingKey($keysKey));
                $keysConfigured = $keysValue !== null;
            }
        }

        header(self::JSON_HEADER);
        echo json_encode([
            'provider_vision' => $providerVision,
            'provider_chat' => $providerChat,
            'keys_configured' => $keysConfigured,
            'assistant_class' => $isConfigured && $keysConfigured ? ucfirst($providerVision) . 'Assistant' : 'NullAssistant',
            'env_override' => getenv('AI_PROVIDER') ?: null,
        ]);
        exit;
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
            $aiVisionStep = new AiVisionStep($aiAssistant);

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
