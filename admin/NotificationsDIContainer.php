<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Auth\AdminGuard;
use Saso\Domain\Setting\SettingKey;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use Saso\Infrastructure\Notification\FcmNotificationService;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class NotificationsDIContainer implements DIContainer
{
    private NotificationsView $view;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new NotificationsView();

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
                $this->view->loadError = 'APP_KEY is not configured.';
                return;
            }

            $settingService = new PdoSystemSettingService($pdo, $encryptor);
            $changedBy      = isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : 'admin';

            $serviceAccountKey = self::readSetting($settingService, 'firebase.service_account_key');
            $projectId         = self::readSetting($settingService, 'firebase.project_id');
            $fcmConfigured     = $serviceAccountKey !== '' && $projectId !== '';

            $this->view->fcmConfigured = $fcmConfigured;

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (!$fcmConfigured) {
                    $this->view->sendError = 'Firebase サービスアカウントキーとプロジェクトIDが設定されていません。ENV設定から先に設定してください。';
                } else {
                    $err = self::handleSend($pdo, $settingService, $post, $changedBy, $projectId, $serviceAccountKey, $now);
                    if ($err === null) {
                        $redirectTo = strtok((string) ($_SERVER['REQUEST_URI'] ?? './admin/notifications/'), '?');
                        header('Location: ' . $redirectTo . '?sent=1', true, 303);
                        exit;
                    }
                    $this->view->sendError = $err;
                }
            }

            $this->view->history = self::loadHistory($pdo);
            $this->view->sent    = isset($_GET['sent']);
        } catch (\Throwable $e) {
            error_log('[notifications] failed: ' . $e);
            $this->view->loadError = $e->getMessage();
        }
    }

    public function flow(): View
    {
        return $this->view ?? new NotificationsView();
    }

    /** @param array<string, mixed> $post */
    private static function handleSend(
        \PDO $pdo,
        PdoSystemSettingService $settingService,
        array $post,
        string $sentBy,
        string $projectId,
        string $serviceAccountKey,
        \DateTime $now,
    ): ?string {
        $title      = trim((string) ($post['notification_title'] ?? ''));
        $body       = trim((string) ($post['notification_body'] ?? ''));
        $targetType = trim((string) ($post['target_type'] ?? 'topic'));
        $target     = trim((string) ($post['target'] ?? ''));
        $imageUrl   = trim((string) ($post['image_url'] ?? '')) ?: null;

        if ($title === '') {
            return 'タイトルは必須です。';
        }
        if ($body === '') {
            return '本文は必須です。';
        }
        if ($target === '') {
            return '送信先（トピックまたはデバイストークン）は必須です。';
        }

        $fcm       = new FcmNotificationService($serviceAccountKey, $projectId);
        $success   = false;
        $messageName = null;
        $errorMsg  = null;

        try {
            $messageName = $targetType === 'token'
                ? $fcm->sendToToken($target, $title, $body, $imageUrl)
                : $fcm->sendToTopic($target, $title, $body, $imageUrl);
            $success = true;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            error_log('[notifications] FCM send failed: ' . $e);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO notification_log
             (title, body, target, target_type, image_url, sent_at, sent_by, success, fcm_message_name, error_message)
             VALUES (:title, :body, :target, :target_type, :image_url, :sent_at, :sent_by, :success, :fcm_name, :error)',
        );
        $stmt->execute([
            ':title'       => $title,
            ':body'        => $body,
            ':target'      => $target,
            ':target_type' => $targetType,
            ':image_url'   => $imageUrl,
            ':sent_at'     => $now->format('Y-m-d H:i:s'),
            ':sent_by'     => $sentBy,
            ':success'     => $success ? 1 : 0,
            ':fcm_name'    => $messageName,
            ':error'       => $errorMsg,
        ]);

        if (!$success) {
            return '送信に失敗しました: ' . $errorMsg;
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private static function loadHistory(\PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT id, title, body, target, target_type, sent_at, sent_by, success, fcm_message_name, error_message
             FROM notification_log
             ORDER BY sent_at DESC
             LIMIT 50',
        );

        if ($stmt === false) {
            return [];
        }

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private static function readSetting(PdoSystemSettingService $svc, string $key): string
    {
        try {
            $val = $svc->get(new SettingKey($key));

            return $val !== null ? $val->asString() : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
