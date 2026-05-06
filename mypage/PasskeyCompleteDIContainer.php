<?php

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;

final class PasskeyCompleteDIContainer implements DIContainer
{
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void {}
    public function flow(): View
    {
        $memberId = (string) ($_SESSION['id'] ?? '');
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
        $challenge = (string) ($payload['challenge'] ?? '');
        $credentialId = (string) ($payload['credentialId'] ?? '');
        $name = trim((string) ($payload['name'] ?? 'Passkey'));
        header('Content-Type: application/json');
        if ($memberId === '' || $challenge === '' || $credentialId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'bad_request']);
            return new EmptyView();
        }
        $pdo = DBConnection::getPdo();
        $check = $pdo->prepare('SELECT challenge FROM webauthn_challenge WHERE challenge = :c AND member_id = :m AND purpose = "registration" AND expires_at > NOW()');
        $check->execute(['c' => $challenge, 'm' => $memberId]);
        if ($check->fetchColumn() === false) {
            http_response_code(400);
            echo json_encode(['error' => 'challenge_expired']);
            return new EmptyView();
        }
        $insert = $pdo->prepare('INSERT INTO webauthn_credential (member_id, credential_id, public_key, sign_count, transports, name, created_at) VALUES (:m, :cid, :pk, 0, :tr, :n, NOW())');
        $insert->bindValue('m', $memberId);
        $insert->bindValue('cid', $credentialId, \PDO::PARAM_LOB);
        $insert->bindValue('pk', (string) ($payload['attestationObject'] ?? ''), \PDO::PARAM_LOB);
        $insert->bindValue('tr', json_encode($payload['transports'] ?? []));
        $insert->bindValue('n', mb_substr($name, 0, 100));
        $insert->execute();
        $pdo->prepare('DELETE FROM webauthn_challenge WHERE challenge = :c')->execute(['c' => $challenge]);
        echo json_encode(['ok' => true]);
        return new EmptyView();
    }
}
