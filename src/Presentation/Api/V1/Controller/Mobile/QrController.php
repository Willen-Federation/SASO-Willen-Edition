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
 * Generates a short-lived pairing code and returns it as:
 *   - `qrDataUri`  — data:image/png;base64,<PNG> ready for an <img> tag
 *   - `deepLink`   — the raw saso://connect URI embedded in the QR
 *   - `expiresAt`  — RFC 3339 UTC expiry
 *   - `label`      — echoed back for UI display
 *
 * The Flutter app scans the QR, extracts `deepLink`, and calls
 * POST /api/v1/mobile/connect with the token from the URI.
 *
 * Body (JSON, optional):
 *   label  string  — human-readable name for this pairing session
 *   url    string  — override the server URL embedded in the QR (useful
 *                    when the server is behind a reverse proxy with a
 *                    different hostname than $_SERVER['HTTP_HOST'])
 */
final class QrController
{
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
        );

        $this->codes->save($code);

        $deepLink = sprintf(
            'saso://connect?token=%s&url=%s',
            urlencode($rawToken),
            urlencode($serverUrl),
        );

        $pngBase64 = $this->renderer->renderBase64($deepLink);

        return new JsonResponse(
            status: 201,
            body: [
                'label'      => $label,
                'deepLink'   => $deepLink,
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
