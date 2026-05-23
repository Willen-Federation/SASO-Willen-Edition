<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Item;

use PDO;
use RuntimeException;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\HttpResponse;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * POST /api/v1/items/auto-register
 *
 * Variant of {@see DraftCreateController} that flags the draft for
 * automatic promotion: after enrichment finishes, the worker writes the
 * row directly to the `item` table (status=active) instead of waiting
 * for manual confirmation.
 *
 * When the `ai.auto_register` feature flag is disabled at processing
 * time, the worker silently degrades to the legacy draft-ready flow so
 * the upload is never lost.
 *
 * Form fields are identical to /items/drafts. Response:
 *   { "draft_id": 123, "status": "queued", "auto_register": true }
 */
final class AutoRegisterController
{
    private const UPLOAD_DIR    = 'uploads/item_drafts/';
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_BYTES     = 20 * 1024 * 1024;

    public function __construct(
        private readonly PDO $pdo,
        private readonly MessageBusInterface $bus,
        private readonly JwtService $jwt,
    ) {
    }

    public function handle(HttpRequest $request): HttpResponse
    {
        $authHeader = $request->header('authorization') ?? '';
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return new JsonResponse(
                status: 401,
                body: ['error' => 'SASO-MOBILE-2001', 'message' => 'Authorization header missing or not Bearer.'],
            );
        }

        $rawToken = substr($authHeader, 7);
        try {
            $deviceTokenId = $this->jwt->verify($rawToken);
        } catch (\RuntimeException) {
            return new JsonResponse(
                status: 401,
                body: ['error' => 'SASO-MOBILE-2002', 'message' => 'Invalid or expired Bearer token.'],
            );
        }

        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            return new JsonResponse(
                status: 400,
                body: ['error' => 'SASO-DRAFT-4001', 'message' => 'Field "image" is required.'],
            );
        }

        $file = $_FILES['image'];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new JsonResponse(
                status: 400,
                body: ['error' => 'SASO-DRAFT-4002', 'message' => 'Image upload failed (error code: '.(int) $file['error'].').'],
            );
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            return new JsonResponse(
                status: 400,
                body: ['error' => 'SASO-DRAFT-4003', 'message' => 'Image exceeds maximum allowed size of 20 MB.'],
            );
        }

        $mimeType = is_string($file['type']) ? $file['type'] : '';
        if (function_exists('mime_content_type') && is_string($file['tmp_name'])) {
            $detectedMime = mime_content_type($file['tmp_name']);
            if ($detectedMime !== false) {
                $mimeType = $detectedMime;
            }
        }

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return new JsonResponse(
                status: 400,
                body: ['error' => 'SASO-DRAFT-4004', 'message' => 'Unsupported image type. Allowed: jpeg, png, webp, gif.'],
            );
        }

        $uploadDir = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? getcwd()), '/').'/'.self::UPLOAD_DIR;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Failed to create upload directory: '.$uploadDir);
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'bin', // @phpstan-ignore match.unreachable
        };

        $filename    = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(8)), $extension);
        $destination = $uploadDir.$filename;
        $imagePath   = self::UPLOAD_DIR.$filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to move uploaded file to: '.$destination);
        }

        $itemName    = trim((string) ($_POST['item_name']    ?? ''));
        $janCode     = trim((string) ($_POST['jan_code']     ?? ''));
        $isbn        = trim((string) ($_POST['isbn']         ?? ''));
        $price       = trim((string) ($_POST['price']        ?? ''));
        $barcodeHint = trim((string) ($_POST['barcode_hint'] ?? ''));

        $userData = [];
        if ($itemName !== '') {
            $userData['item_name'] = $itemName;
        }
        if ($janCode !== '') {
            $userData['jan_code'] = $janCode;
        }
        if ($isbn !== '') {
            $userData['isbn'] = $isbn;
        }
        if ($price !== '') {
            $userData['price'] = $price;
        }
        $userDataJson = $userData !== [] ? json_encode($userData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $barcodeHintValue = $barcodeHint !== '' ? $barcodeHint : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO item_draft (image_path, barcode_hint, user_data, status, auto_register, created_by, created_at, updated_at) '
            .'VALUES (:image_path, :barcode_hint, :user_data, :status, :auto_register, :created_by, NOW(), NOW())',
        );
        $stmt->bindValue('image_path', $imagePath);
        $stmt->bindValue('barcode_hint', $barcodeHintValue);
        $stmt->bindValue('user_data', $userDataJson);
        $stmt->bindValue('status', 'queued');
        $stmt->bindValue('auto_register', 1, PDO::PARAM_INT);
        $stmt->bindValue('created_by', $deviceTokenId, PDO::PARAM_INT);
        $stmt->execute();

        $draftId = (int) $this->pdo->lastInsertId();

        $this->bus->dispatch(new ProcessItemDraft($draftId));

        return new JsonResponse(
            status: 202,
            body: [
                'draft_id'      => $draftId,
                'status'        => 'queued',
                'auto_register' => true,
            ],
        );
    }
}
