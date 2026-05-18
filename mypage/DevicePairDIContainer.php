<?php

namespace saso\mypage;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Infrastructure\MobileConnect\PdoPairingCodeRepository;
use Saso\Infrastructure\MobileConnect\QrCodeRenderer;
use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\CSRFtoken;

/**
 * POST /mypage/devicePair
 *
 * Self-service pairing-code issuer for the logged-in user. Mirrors the
 * admin-only POST /api/v1/mobile/pairing-codes flow but enforces three
 * additional guarantees:
 *
 *  1. session-bound: the new code's member_id is pinned to $_SESSION['id'],
 *     not a request parameter.
 *  2. CSRF-protected: rejects requests without a valid session token.
 *  3. JSON-only response: the raw token is returned exactly once, never
 *     persisted in plaintext, and never logged.
 */
final class DevicePairDIContainer implements DIContainer
{
    private const PAYLOAD_PREFIX = 'SASO1:';

    private array $post = [];

    public function isTopLevel(): bool
    {
        // JSON-only AJAX endpoint: must bypass the root template, otherwise
        // the HTML shell is appended to the JSON body and `await res.json()`
        // in the device-pairing modal throws (surfaces as "Failed to generate
        // code" in the UI even though the row was written to pairing_code).
        return true;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->post = $post;
    }

    public function flow(): View
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $memberId = (string) ($_SESSION['id'] ?? '');
        if ($memberId === '') {
            http_response_code(401);
            echo json_encode(['error' => 'unauthenticated']);
            return new EmptyView();
        }

        if (!CSRFtoken::verify((string) ($this->post['csrftoken'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['error' => 'csrf_invalid']);
            return new EmptyView();
        }

        $deviceName = trim((string) ($this->post['device_name'] ?? ''));
        if ($deviceName === '') {
            $deviceName = 'My Device';
        }
        if (mb_strlen($deviceName) > 200) {
            $deviceName = mb_substr($deviceName, 0, 200);
        }

        try {
            $repo     = new PdoPairingCodeRepository(DBConnection::getPdo());
            $renderer = new QrCodeRenderer();

            $now      = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $expiry   = $now->modify(sprintf('+%d minutes', PairingCode::TTL_MINUTES));
            $rawToken = PairingCode::generateRawToken();
            $hash     = PairingCode::hashToken($rawToken);

            $code = new PairingCode(
                id: $repo->nextId(),
                tokenHash: $hash,
                label: $deviceName,
                used: false,
                expiresAt: $expiry,
                createdAt: $now,
                memberId: $memberId,
            );
            $repo->save($code);

            $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $serverUrl = $proto.'://'.$host;

            $qrPayload = self::PAYLOAD_PREFIX.$rawToken.'|'.rtrim($serverUrl, '/');
            $qrBase64  = $renderer->renderBase64($qrPayload);

            echo json_encode([
                'qrPayload'  => $qrPayload,
                'qrDataUri'  => 'data:image/png;base64,'.$qrBase64,
                'rawToken'   => $rawToken,
                'expiresAt'  => $expiry->format(DateTimeInterface::RFC3339),
                'ttlSeconds' => PairingCode::TTL_MINUTES * 60,
                'deviceName' => $deviceName,
            ]);
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-mypage-device-pair] '.$e->getMessage());
            }
            http_response_code(500);
            echo json_encode(['error' => 'server_error']);
        }

        return new EmptyView();
    }
}
