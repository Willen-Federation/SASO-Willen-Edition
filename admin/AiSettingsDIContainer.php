<?php
namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Auth\AdminGuard;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
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

        try {
            $pdo        = DBConnection::getPdo();
            $authorized = (new AdminGuard($pdo))->isAdmin(
                isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
            );

            $this->view->authorized = $authorized;

            if (!$authorized) {
                return;
            }

            $encryptor = AppKeyResolver::tryEncryptor();
            if ($encryptor === null) {
                $this->view->loadError = 'APP_KEY is not configured. Set APP_KEY in .env to a 32-byte base64 / 64-char hex / ≥32-char string.';
                $this->view->settings  = self::defaultSettings();
                return;
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
        } catch (\Throwable $e) {
            error_log('[ai-settings] load failed: ' . $e);
            $this->view->settings  = self::defaultSettings();
            $this->view->loadError = $e->getMessage();
        }
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

        // API keys — multiple per provider, stored as JSON arrays so
        // AiAssistantFactory can rotate through them via FallbackChainAssistant.
        $settingService->set(
            new SettingKey('ai.openai_api_keys'),
            SettingValue::json(self::normalizeKeys($post['ai_openai_api_keys'] ?? [])),
            $changedBy,
            'Updated via AI Settings UI',
        );
        $settingService->set(
            new SettingKey('ai.gemini_api_keys'),
            SettingValue::json(self::normalizeKeys($post['ai_gemini_api_keys'] ?? [])),
            $changedBy,
            'Updated via AI Settings UI',
        );
        $settingService->set(
            new SettingKey('ai.anthropic_api_keys'),
            SettingValue::json(self::normalizeKeys($post['ai_anthropic_api_keys'] ?? [])),
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
        $visionVal    = $settingService->get(new SettingKey('ai.provider_vision'));
        $chatVal      = $settingService->get(new SettingKey('ai.provider_chat'));
        $promptJaVal  = $settingService->get(new SettingKey('ai.prompt_ja'));
        $promptEnVal  = $settingService->get(new SettingKey('ai.prompt_en'));
        $rateVal      = $settingService->get(new SettingKey('messaging.rate_limit'));
        $openaiVal    = $settingService->get(new SettingKey('ai.openai_api_keys'));
        $geminiVal    = $settingService->get(new SettingKey('ai.gemini_api_keys'));
        $anthropicVal = $settingService->get(new SettingKey('ai.anthropic_api_keys'));

        return [
            'ai_provider_vision'    => $visionVal   !== null ? $visionVal->asString()   : '',
            'ai_provider_chat'      => $chatVal      !== null ? $chatVal->asString()      : '',
            'ai_prompt_ja'          => $promptJaVal  !== null ? $promptJaVal->asString()  : '',
            'ai_prompt_en'          => $promptEnVal  !== null ? $promptEnVal->asString()  : '',
            'messaging_rate_limit'  => $rateVal      !== null ? $rateVal->asInt()         : 10,
            'ai_openai_api_keys'    => self::toKeyList($openaiVal),
            'ai_gemini_api_keys'    => self::toKeyList($geminiVal),
            'ai_anthropic_api_keys' => self::toKeyList($anthropicVal),
        ];
    }

    /** @return array<string, mixed> */
    private static function defaultSettings(): array
    {
        return [
            'ai_provider_vision'    => '',
            'ai_provider_chat'      => '',
            'ai_prompt_ja'          => '',
            'ai_prompt_en'          => '',
            'messaging_rate_limit'  => 10,
            'ai_openai_api_keys'    => [],
            'ai_gemini_api_keys'    => [],
            'ai_anthropic_api_keys' => [],
        ];
    }

    /**
     * Cleans posted API keys: trims each, drops empties, deduplicates
     * while preserving submission order, and caps the list at a sane
     * upper bound so a runaway form post cannot persist thousands of
     * keys. The cap mirrors what FallbackChainAssistant can usefully
     * cycle through in a single request.
     *
     * @param mixed $posted raw value from $_POST (typically array<int,string>)
     *
     * @return list<string>
     */
    private static function normalizeKeys(mixed $posted): array
    {
        if (!is_array($posted)) {
            return [];
        }

        $seen = [];
        $out  = [];
        foreach ($posted as $value) {
            if (!is_string($value)) {
                continue;
            }
            $trimmed = trim($value);
            if ($trimmed === '' || isset($seen[$trimmed])) {
                continue;
            }
            $seen[$trimmed] = true;
            $out[]          = $trimmed;
            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }

    /**
     * Normalises a stored API-key setting to a list<string>.
     *
     * Tolerates legacy data that may be a JSON array, a JSON string
     * (single key), or a plain string saved before the multi-key UI.
     *
     * @return list<string>
     */
    private static function toKeyList(?SettingValue $value): array
    {
        if ($value === null) {
            return [];
        }

        $decoded = $value->asJson();
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $item) {
                if (is_string($item) && $item !== '') {
                    $out[] = $item;
                }
            }

            return $out;
        }

        if (is_string($decoded) && $decoded !== '') {
            return [$decoded];
        }

        $raw = trim($value->asString());

        return $raw === '' ? [] : [$raw];
    }
}
