<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Notification;

final class FcmNotificationService
{
    public function __construct(
        private readonly string $serviceAccountJson,
        private readonly string $projectId,
    ) {}

    /**
     * @return string FCM message name (e.g. "projects/.../messages/...")
     * @throws \RuntimeException
     */
    public function sendToTopic(string $topic, string $title, string $body, ?string $imageUrl = null): string
    {
        $token = $this->getAccessToken();

        return $this->dispatch($token, [
            'topic'        => $topic,
            'notification' => $this->buildNotification($title, $body, $imageUrl),
        ]);
    }

    /**
     * @return string FCM message name
     * @throws \RuntimeException
     */
    public function sendToToken(string $deviceToken, string $title, string $body, ?string $imageUrl = null): string
    {
        $token = $this->getAccessToken();

        return $this->dispatch($token, [
            'token'        => $deviceToken,
            'notification' => $this->buildNotification($title, $body, $imageUrl),
        ]);
    }

    /** @return array<string, string> */
    private function buildNotification(string $title, string $body, ?string $imageUrl): array
    {
        $n = ['title' => $title, 'body' => $body];
        if ($imageUrl !== null && $imageUrl !== '') {
            $n['image'] = $imageUrl;
        }

        return $n;
    }

    /** @throws \RuntimeException */
    private function getAccessToken(): string
    {
        $creds = json_decode($this->serviceAccountJson, true);
        if (!is_array($creds) || !isset($creds['client_email'], $creds['private_key'])) {
            throw new \RuntimeException('Invalid service account JSON: missing client_email or private_key.');
        }

        $now  = time();
        $head = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $pay  = $this->b64url(json_encode([
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $signingInput = $head . '.' . $pay;
        $signature    = '';

        if (!openssl_sign($signingInput, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('JWT signing failed: ' . (openssl_error_string() ?: 'unknown error'));
        }

        $jwt = $signingInput . '.' . $this->b64url($signature);

        $resp = $this->post(
            'https://oauth2.googleapis.com/token',
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            ['Content-Type: application/x-www-form-urlencoded'],
        );

        $data = json_decode($resp, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new \RuntimeException('Access-token exchange failed: ' . $resp);
        }

        return (string) $data['access_token'];
    }

    /**
     * @param array<string, mixed> $message
     * @throws \RuntimeException
     */
    private function dispatch(string $accessToken, array $message): string
    {
        $url  = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $body = json_encode(['message' => $message], JSON_THROW_ON_ERROR);

        $resp = $this->post($url, $body, [
            'Content-Type: application/json',
            "Authorization: Bearer {$accessToken}",
        ]);

        $data = json_decode($resp, true);
        if (!is_array($data) || empty($data['name'])) {
            throw new \RuntimeException('FCM send failed: ' . $resp);
        }

        return (string) $data['name'];
    }

    /**
     * @param list<string> $headers
     * @throws \RuntimeException
     */
    private function post(string $url, string $body, array $headers): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new \RuntimeException('HTTP request failed: ' . $err);
        }

        return (string) $result;
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
