<?php
namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Auth\AdminGuard;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class AiSettingsDIContainer implements DIContainer
{
    private AiSettingsView $view;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new AiSettingsView();

        $pdo        = DBConnection::getPdo();
        $authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );

        $this->view->authorized = $authorized;

        if (!$authorized) {
            return;
        }

        $appKey    = (string) (getenv('APP_KEY') ?: '');
        $encryptor = new SecretEncryptor(str_repeat("\x00", 32));
        if ($appKey !== '') {
            $rawKey = base64_decode($appKey, true);
            if ($rawKey !== false && strlen($rawKey) === 32) {
                $encryptor = new SecretEncryptor($rawKey);
            }
        }

        $settingService = new PdoSystemSettingService($pdo, $encryptor);
        $changedBy      = isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : 'admin';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::handlePost($settingService, $post, $changedBy);
            $redirectTo = strtok((string) ($_SERVER['REQUEST_URI'] ?? './admin/ai-settings/'), '?');
            header('Location: ' . $redirectTo . '?saved=1', true, 303);
            exit;
        }

        $this->view->settings = self::loadSettings($settingService);
        $this->view->saved    = isset($_GET['saved']);
    }

    public function flow(): View
    {
        return $this->view ?? new AiSettingsView();
    }

    /** @param array<string, mixed> $post */
    private static function handlePost(PdoSystemSettingService $settingService, array $post, string $changedBy): void
    {
        // Vision provider
        $settingService->set(
            new SettingKey('ai.provider_vision'),
            SettingValue::string(trim((string) ($post['ai_provider_vision'] ?? ''))),
            $changedBy,
            'Updated via AI Settings UI',
        );

        // Chat provider
        $settingService->set(
            new SettingKey('ai.provider_chat'),
            SettingValue::string(trim((string) ($post['ai_provider_chat'] ?? ''))),
            $changedBy,
            'Updated via AI Settings UI',
        );

        // OpenAI API keys
        $openaiKeys = array_values(array_filter(
            array_map('trim', (array) ($post['ai_openai_api_keys'] ?? [])),
            fn (string $k): bool => $k !== '',
        ));
        $settingService->set(
            new SettingKey('ai.openai_api_keys'),
            SettingValue::json($openaiKeys),
            $changedBy,
            'Updated via AI Settings UI',
        );

        // Gemini API keys
        $geminiKeys = array_values(array_filter(
            array_map('trim', (array) ($post['ai_gemini_api_keys'] ?? [])),
            fn (string $k): bool => $k !== '',
        ));
        $settingService->set(
            new SettingKey('ai.gemini_api_keys'),
            SettingValue::json($geminiKeys),
            $changedBy,
            'Updated via AI Settings UI',
        );

        // Anthropic API keys
        $anthropicKeys = array_values(array_filter(
            array_map('trim', (array) ($post['ai_anthropic_api_keys'] ?? [])),
            fn (string $k): bool => $k !== '',
        ));
        $settingService->set(
            new SettingKey('ai.anthropic_api_keys'),
            SettingValue::json($anthropicKeys),
            $changedBy,
            'Updated via AI Settings UI',
        );

        // Extraction prompts
        $settingService->set(
            new SettingKey('ai.prompt_ja'),
            SettingValue::string((string) ($post['ai_prompt_ja'] ?? '')),
            $changedBy,
            'Updated via AI Settings UI',
        );
        $settingService->set(
            new SettingKey('ai.prompt_en'),
            SettingValue::string((string) ($post['ai_prompt_en'] ?? '')),
            $changedBy,
            'Updated via AI Settings UI',
        );

        // Rate limit
        $settingService->set(
            new SettingKey('messaging.rate_limit'),
            SettingValue::int(max(1, (int) ($post['messaging_rate_limit'] ?? 10))),
            $changedBy,
            'Updated via AI Settings UI',
        );
    }

    /** @return array<string, mixed> */
    private static function loadSettings(PdoSystemSettingService $settingService): array
    {
        $visionVal   = $settingService->get(new SettingKey('ai.provider_vision'));
        $chatVal     = $settingService->get(new SettingKey('ai.provider_chat'));
        $promptJaVal = $settingService->get(new SettingKey('ai.prompt_ja'));
        $promptEnVal = $settingService->get(new SettingKey('ai.prompt_en'));
        $rateVal     = $settingService->get(new SettingKey('messaging.rate_limit'));
        $openaiVal   = $settingService->get(new SettingKey('ai.openai_api_keys'));
        $geminiVal   = $settingService->get(new SettingKey('ai.gemini_api_keys'));
        $anthropicVal = $settingService->get(new SettingKey('ai.anthropic_api_keys'));

        return [
            'ai_provider_vision'    => $visionVal   !== null ? $visionVal->asString()   : '',
            'ai_provider_chat'      => $chatVal      !== null ? $chatVal->asString()      : '',
            'ai_prompt_ja'          => $promptJaVal  !== null ? $promptJaVal->asString()  : '',
            'ai_prompt_en'          => $promptEnVal  !== null ? $promptEnVal->asString()  : '',
            'messaging_rate_limit'  => $rateVal      !== null ? $rateVal->asInt()         : 10,
            'ai_openai_api_keys'    => $openaiVal    !== null ? (array) $openaiVal->asJson()    : [],
            'ai_gemini_api_keys'    => $geminiVal    !== null ? (array) $geminiVal->asJson()    : [],
            'ai_anthropic_api_keys' => $anthropicVal !== null ? (array) $anthropicVal->asJson() : [],
        ];
    }
}
