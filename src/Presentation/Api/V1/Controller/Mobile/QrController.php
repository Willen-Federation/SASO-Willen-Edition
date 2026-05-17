<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Domain\MobileConnect\Repository\PairingCodeRepository;
use Saso\Infrastructure\MobileConnect\QrCodeRenderer;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * POST /api/v1/mobile/pairing-codes
 *
 * Generates a short-lived pairing token and returns:
 *   - `qrDataUri`  — data:image/png;base64,<PNG> for embedding in an <img> tag
 *   - `qrPayload`  — the raw string encoded into the QR (for custom rendering)
 *   - `expiresAt`  — RFC 3339 UTC expiry (10 minutes)
 *   - `label`      — echoed back
 *
 * QR payload format (proprietary, app-only readable):
 *
 *   SASO1:<base64url_token>|<server_base_url>
 *
 * The `SASO1:` prefix is a magic marker recognised exclusively by the
 * SASO Flutter app. A generic QR scanner will display opaque text and
 * offer no actionable link — so the token cannot be accidentally
 * intercepted by OS-level deep-link routing or browser auto-open.
 *
 * The Flutter app reads the QR text, detects the `SASO1:` prefix,
 * splits on `|`, extracts the raw token and server URL, then calls
 * POST /api/v1/mobile/connect automatically — no user interaction
 * beyond pointing the camera.
 *
 * Body (JSON, optional):
 *   label  string  — human-readable name for this pairing session
 *   url    string  — override the server URL embedded in the QR
 */
final class QrController
{
    private const PAYLOAD_PREFIX = 'SASO1:';

    public function __construct(
        private readonly PairingCodeRepository $codes,
        private readonly QrCodeRenderer $renderer,
        private readonly string $serverBaseUrl = '',
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body  = $this->parseBody($request);
        $label = trim((string) ($body['label'] ?? 'Pairing'));
        if ($label === '') {
            $label = 'Pairing';
        }

        $serverUrl = trim((string) ($body['url'] ?? $this->serverBaseUrl));
        if ($serverUrl === '') {
            $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $serverUrl = $proto.'://'.$host;
        }

        // Bootstrap::requireSessionAuth() runs before this handler, so
        // $_SESSION['id'] is guaranteed present and non-empty. Bind the
        // pairing code to that admin so the eventual device JWT can claim
        // a real principal (cf. mobile-pairing hardening, issue #204).
        $memberId = isset($_SESSION['id']) && is_string($_SESSION['id']) && $_SESSION['id'] !== ''
            ? $_SESSION['id']
            : null;

        $now      = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiry   = $now->modify(sprintf('+%d minutes', PairingCode::TTL_MINUTES));
        $rawToken = PairingCode::generateRawToken();
        $hash     = PairingCode::hashToken($rawToken);

        $code = new PairingCode(
            id: $this->codes->nextId(),
            tokenHash: $hash,
            label: $label,
            used: false,
            expiresAt: $expiry,
            createdAt: $now,
            memberId: $memberId,
        );

        $this->codes->save($code);

        $qrPayload = self::PAYLOAD_PREFIX.$rawToken.'|'.rtrim($serverUrl, '/');
        $pngBase64 = $this->renderer->renderBase64($qrPayload);

        return new JsonResponse(
            status: 201,
            body: [
                'label'      => $label,
                'qrPayload'  => $qrPayload,
                'qrDataUri'  => 'data:image/png;base64,'.$pngBase64,
                'expiresAt'  => $expiry->format(DateTimeInterface::RFC3339),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function parseBody(HttpRequest $request): array
    {
        if ($request->body === null || $request->body === '') {
            return [];
        }

        $decoded = json_decode($request->body, associative: true);

        return is_array($decoded) ? $decoded : [];
    }
}
