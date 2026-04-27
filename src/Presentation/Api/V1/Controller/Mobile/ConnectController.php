<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Mobile;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Exception\PairingCodeExpiredException;
use Saso\Domain\MobileConnect\Exception\PairingCodeNotFoundException;
use Saso\Domain\MobileConnect\Exception\PairingCodeUsedException;
use Saso\Domain\MobileConnect\PairingCode;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Domain\MobileConnect\Repository\PairingCodeRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * POST /api/v1/mobile/connect
 *
 * Exchanges a one-time pairing code for a long-lived device token.
 *
 * The Flutter app:
 *   1. Scans the QR code produced by POST /api/v1/mobile/pairing-codes.
 *   2. Extracts `token` from the deep-link query string.
 *   3. Posts `{ "token": "<raw>", "deviceName": "My Phone" }` to this endpoint.
 *   4. Receives `{ "deviceToken": "<raw>", "expiresAt": "..." }` in the response.
 *   5. Stores the device token locally and includes it as
 *      `Authorization: Bearer <deviceToken>` on subsequent API calls.
 *
 * Body (JSON):
 *   token       string  required — raw URL-safe base64 token from the QR URI
 *   deviceName  string  required — human-readable device label (e.g. "iPad mini")
 */
final class ConnectController
{
    public function __construct(
        private readonly PairingCodeRepository $codes,
        private readonly DeviceTokenRepository $tokens,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $body       = $this->parseBody($request);
        $rawToken   = trim((string) ($body['token'] ?? ''));
        $deviceName = trim((string) ($body['deviceName'] ?? ''));

        if ($rawToken === '' || $deviceName === '') {
            throw new \InvalidArgumentException('Fields "token" and "deviceName" are required.');
        }

        $hash = PairingCode::hashToken($rawToken);
        $code = $this->codes->findByTokenHash($hash);

        if ($code === null) {
            throw new PairingCodeNotFoundException();
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($code->used) {
            throw new PairingCodeUsedException();
        }

        if ($code->isExpired($now)) {
            throw new PairingCodeExpiredException();
        }

        $this->codes->save($code->markUsed());

        $rawDeviceToken = DeviceToken::generateRawToken();
        $deviceToken    = new DeviceToken(
            id: $this->tokens->nextId(),
            tokenHash: DeviceToken::hashToken($rawDeviceToken),
            deviceName: $deviceName,
            revoked: false,
            lastUsedAt: null,
            expiresAt: $now->modify(sprintf('+%d days', DeviceToken::TTL_DAYS)),
            createdAt: $now,
        );

        $saved = $this->tokens->save($deviceToken);

        return new JsonResponse(
            status: 201,
            body: [
                'id'          => $saved->id,
                'deviceToken' => $rawDeviceToken,
                'deviceName'  => $saved->deviceName,
                'expiresAt'   => $saved->expiresAt->format(DateTimeInterface::RFC3339),
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
