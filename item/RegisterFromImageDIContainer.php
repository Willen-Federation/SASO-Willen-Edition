<?php

namespace saso\item;

use Saso\Domain\Messaging\Message\ProcessItemDraft;
use saso\framework\DIContainer;
use saso\framework\View;
use Saso\Infrastructure\Messaging\MessageBusFactory;
use saso\repository\DBConnection;
use saso\util\monad\Left;
use saso\util\UploadValidator;

final class RegisterFromImageDIContainer implements DIContainer
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_BYTES     = 20 * 1024 * 1024; // 20 MB

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

        // Validate uploaded file via the shared UploadValidator: this rebuilds
        // the MIME type from the file's actual bytes (never trusts the client),
        // confirms the upload came through SAPI, enforces a size ceiling, and
        // returns a safe extension derived from the validated MIME.
        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            return $this->respondError('No image file uploaded.', 400);
        }

        $validation = UploadValidator::validateImageUpload($file, self::ALLOWED_MIMES, self::MAX_BYTES);
        if ($validation instanceof Left) {
            $reason = null;
            $validation->orElse(function ($v) use (&$reason): void {
                $reason = is_string($v) ? $v : 'invalid upload';
            });
            return $this->respondError('Image upload rejected: '.($reason ?? 'invalid upload'), 400);
        }

        $validated = null;
        $validation->map(function ($v) use (&$validated) {
            $validated = $v;
            return $v;
        });
        /** @var array{tmp_name:string,mimeType:string,size:int,extension:string} $validated */

        // Determine upload directory and ensure it has a PHP-execution block
        // dropped alongside it so any future regression that writes an arbitrary
        // file into the tree cannot execute as PHP.
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html'), '/');
        $uploadDir = $docRoot.'/uploads/item_drafts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        self::ensureNoExecutePolicy($docRoot.'/uploads/');

        // Generate unique filename — extension comes from the validated MIME,
        // never from the user-supplied filename.
        $filename = uniqid('draft_', true).'.'.$validated['extension'];
        $destPath = $uploadDir.$filename;

        if (!move_uploaded_file($validated['tmp_name'], $destPath)) {
            return $this->respondError('Failed to save uploaded image.', 500);
        }

        $imagePath = 'uploads/item_drafts/'.$filename;

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
            error_log('[saso-draft] dispatch failed: '.$e->getMessage());
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

    private function respondError(string $message, int $status): View
    {
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
            echo json_encode(['error' => $message]);
            exit;
        }
        $_SESSION['flash_error'] = $message;
        return new AddFromImageView();
    }

    /**
     * Drop a static .htaccess into the uploads root that disables PHP handlers.
     * Defence-in-depth: even if a future regression places a .php file here,
     * Apache must not execute it.
     */
    public static function ensureNoExecutePolicy(string $uploadsRoot): void
    {
        if (!is_dir($uploadsRoot)) {
            return;
        }
        $target = rtrim($uploadsRoot, '/').'/.htaccess';
        if (is_file($target)) {
            return;
        }
        $policy = <<<APACHE
# Auto-generated by SASO upload handler. Do not edit by hand.
# Blocks PHP execution inside the uploads tree as defence-in-depth against
# unrestricted-upload vulnerabilities (CWE-434).
<FilesMatch "\\.(php|phtml|phar|inc|pl|py|jsp|asp|sh|cgi)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

<IfModule mod_mime.c>
    RemoveHandler .php .phtml .phar .inc
    RemoveType .php .phtml .phar .inc
</IfModule>

<IfModule mod_php.c>
    php_flag engine off
</IfModule>

<IfModule mod_php7.c>
    php_flag engine off
</IfModule>

<IfModule mod_php8.c>
    php_flag engine off
</IfModule>

Options -ExecCGI

APACHE;
        @file_put_contents($target, $policy);
    }
}
