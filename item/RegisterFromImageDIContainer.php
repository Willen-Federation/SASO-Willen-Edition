<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Infrastructure\Messaging\MessageBusFactory;

final class RegisterFromImageDIContainer implements DIContainer
{
    private \DateTime $now;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->now = $now;
    }

    public function flow(): View
    {
        $now = $this->now;

        // GET requests — show the upload form
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            return new AddFromImageView();
        }

        $pdo = DBConnection::pdo();

        // Validate uploaded file
        if (
            !isset($_FILES['image'])
            || $_FILES['image']['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file($_FILES['image']['tmp_name'])
        ) {
            $errorMsg = isset($_FILES['image'])
                ? 'Image upload error: ' . $_FILES['image']['error']
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

        // Determine upload directory
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html'), '/');
        $uploadDir = $docRoot . '/uploads/item_drafts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $ext = strtolower($ext ?: 'jpg');
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
            $view = new AddFromImageView();
            return $view;
        }

        $imagePath = 'uploads/item_drafts/' . $filename;

        // Build userData from non-empty POST fields
        $userData = [];
        $userFields = ['item_name', 'jan_code', 'isbn', 'price', 'barcode_hint'];
        foreach ($userFields as $field) {
            if (!empty($_POST[$field])) {
                $userData[$field] = (string) $_POST[$field];
            }
        }

        $barcodeHint = isset($userData['barcode_hint']) ? $userData['barcode_hint'] : null;
        $userDataJson = empty($userData) ? null : json_encode($userData, JSON_UNESCAPED_UNICODE);
        $nowStr = $now->format('Y-m-d H:i:s');

        // INSERT item_draft row
        $stmt = $pdo->prepare(
            'INSERT INTO item_draft (image_path, barcode_hint, user_data, status, created_at, updated_at)
             VALUES (:image_path, :barcode_hint, :user_data, :status, :created_at, :updated_at)'
        );
        $stmt->execute([
            'image_path'   => $imagePath,
            'barcode_hint' => $barcodeHint,
            'user_data'    => $userDataJson,
            'status'       => 'queued',
            'created_at'   => $nowStr,
            'updated_at'   => $nowStr,
        ]);
        $draftId = (int) $pdo->lastInsertId();

        // Dispatch ProcessItemDraft message via sync bus
        try {
            $bus = MessageBusFactory::create([
                ProcessItemDraft::class => [
                    // Placeholder no-op handler — real handler wired in async task
                    static function (ProcessItemDraft $msg): void {
                        // async transport will handle this in the next milestone
                    },
                ],
            ]);
            $bus->dispatch(new ProcessItemDraft($draftId));
        } catch (\Throwable $e) {
            // Log but do not fail — draft is already queued
            error_log('[saso-draft] dispatch failed: ' . $e->getMessage());
        }

        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(201);
            echo json_encode(['draft_id' => $draftId, 'status' => 'queued']);
            exit;
        }

        $_SESSION['flash_success'] = 'Draft created. We\'ll analyse the image and let you know when it\'s ready.';
        \saso\util\Redirect::redirect('item/drafts/');
        exit; // @phpstan-ignore-line — redirect always exits
    }

    private function isAjax(): bool
    {
        if (isset($_GET['_ajax']) && $_GET['_ajax'] === '1') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
