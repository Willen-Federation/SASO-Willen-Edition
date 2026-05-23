<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Application\Messaging\ProcessItemDraftDIContainer;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;
use Saso\Infrastructure\ItemDraft\PdoItemDraftRepository;
use Saso\Infrastructure\Messaging\MessageBusFactory;
use Saso\Infrastructure\Setting\PdoSystemSettingService;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class AddFromImageDIContainer implements DIContainer
{
    private array $post = [];
    private \DateTime $now;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->post = $post;
        $this->now  = $now;
    }

    public function flow(): View
    {
        // GET — show upload form
        if (empty($this->post) && empty($_FILES['image'])) {
            return new AddFromImageView();
        }

        // POST — process upload
        $pdo = DBConnection::pdo();

        // Validate uploaded file
        if (
            !isset($_FILES['image'])
            || $_FILES['image']['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file($_FILES['image']['tmp_name'])
        ) {
            $errorMsg = isset($_FILES['image'])
                ? 'Image upload error (code ' . $_FILES['image']['error'] . ').'
                : 'No image file uploaded.';

            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['error' => $errorMsg]);
                exit;
            }
            $_SESSION['flash_error'] = $errorMsg;
            $view = new AddFromImageView();
            return $view;
        }

        // Determine upload directory relative to document root
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html'), '/');
        $uploadDir = $docRoot . '/uploads/item_drafts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) ?: 'jpg');
        $filename = uniqid('draft_', true) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
            $errorMsg = 'Failed to save uploaded image.';
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode(['error' => $errorMsg]);
                exit;
            }
            $_SESSION['flash_error'] = $errorMsg;
            return new AddFromImageView();
        }

        $imagePath = 'uploads/item_drafts/' . $filename;

        // Build userData from non-empty POST fields
        $userData = [];
        foreach (['item_name', 'jan_code', 'isbn', 'price', 'barcode_hint'] as $field) {
            if (!empty($this->post[$field])) {
                $userData[$field] = (string) $this->post[$field];
            }
        }

        $barcodeHint  = $userData['barcode_hint'] ?? null;
        $userDataJson = empty($userData) ? null : json_encode($userData, JSON_UNESCAPED_UNICODE);
        $nowStr       = $this->now->format('Y-m-d H:i:s');
        $createdBy    = isset($_SESSION['id']) ? (int) $_SESSION['id'] : null;
        $autoRegister = !empty($this->post['auto_register']) ? 1 : 0;

        $stmt = $pdo->prepare(
            'INSERT INTO item_draft
                (image_path, barcode_hint, user_data, status, auto_register, created_by, created_at, updated_at)
             VALUES
                (:image_path, :barcode_hint, :user_data, :status, :auto_register, :created_by, :created_at, :updated_at)'
        );
        $stmt->execute([
            'image_path'    => $imagePath,
            'barcode_hint'  => $barcodeHint,
            'user_data'     => $userDataJson,
            'status'        => 'queued',
            'auto_register' => $autoRegister,
            'created_by'    => $createdBy,
            'created_at'    => $nowStr,
            'updated_at'    => $nowStr,
        ]);
        $draftId = (int) $pdo->lastInsertId();

        // Dispatch ProcessItemDraft message via sync bus
        try {
            $draftRepository = new PdoItemDraftRepository($pdo);
            $settingService = new PdoSystemSettingService($pdo, new SecretEncryptor());
            $flagRepository = new PdoFeatureFlagRepository($pdo);
            $handler = ProcessItemDraftDIContainer::createHandler($draftRepository, $settingService, $flagRepository, $pdo);

            $bus = MessageBusFactory::create([
                ProcessItemDraft::class => [$handler],
            ]);
            $bus->dispatch(new ProcessItemDraft($draftId));
        } catch (\Throwable $e) {
            error_log('[saso-draft] dispatch failed: ' . $e->getMessage());
        }

        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode([
                'draft_id'      => $draftId,
                'status'        => 'queued',
                'auto_register' => (bool) $autoRegister,
            ]);
            exit;
        }

        $_SESSION['flash_success'] = $autoRegister
            ? 'AI自動登録を受け付けました。バックグラウンドで処理しています。'
            : 'Draft created. We\'ll analyse the image and let you know when it\'s ready.';
        \saso\util\Redirect::redirect($autoRegister ? 'item/list/' : 'item/drafts/');
        exit;
    }

    private function isAjax(): bool
    {
        if (isset($_GET['_ajax']) && $_GET['_ajax'] === '1') {
            return true;
        }
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
