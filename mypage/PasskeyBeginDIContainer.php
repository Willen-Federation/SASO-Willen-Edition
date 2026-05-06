<?php

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;

final class PasskeyBeginDIContainer implements DIContainer
{
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void {}
    public function flow(): View
    {
        $memberId = (string) ($_SESSION['id'] ?? '');
        if ($memberId === '') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'auth_required']);
            return new EmptyView();
        }
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $pdo = DBConnection::getPdo();
        $stmt = $pdo->prepare('INSERT INTO webauthn_challenge (challenge, member_id, purpose, created_at, expires_at) VALUES (:c, :m, "registration", NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE))');
        $stmt->execute(['c' => $challenge, 'm' => $memberId]);
        header('Content-Type: application/json');
        echo json_encode([
            'challenge' => $challenge,
            'rpId' => $_SERVER['HTTP_HOST'] ? explode(':', (string) $_SERVER['HTTP_HOST'])[0] : 'localhost',
            'rpName' => 'SASO',
            'userId' => rtrim(strtr(base64_encode($memberId), '+/', '-_'), '='),
            'userName' => $memberId,
            'displayName' => (string) ($_SESSION['userName'] ?? $memberId),
        ], JSON_UNESCAPED_SLASHES);
        return new EmptyView();
    }
}
