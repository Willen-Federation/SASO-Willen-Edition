<?php

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\CSRFtoken;

final class PasskeyDeleteDIContainer implements DIContainer
{
    private array $post = [];
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void { $this->post = $post; }
    public function flow(): View
    {
        $memberId = (string) ($_SESSION['id'] ?? '');
        if ($memberId !== '' && CSRFtoken::verify((string) ($this->post['csrftoken'] ?? ''))) {
            $stmt = DBConnection::getPdo()->prepare('DELETE FROM webauthn_credential WHERE id = :id AND member_id = :m');
            $stmt->execute(['id' => (int) ($this->post['id'] ?? 0), 'm' => $memberId]);
        }
        \saso\util\Redirect::redirect('mypage/start/?passkey=deleted');
        return new EmptyView();
    }
}
