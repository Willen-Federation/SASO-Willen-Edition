<?php

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\CSRFtoken;

final class UnlinkProviderDIContainer implements DIContainer
{
    private array $post = [];

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->post = $post;
    }

    public function flow(): View
    {
        $memberId = (string) ($_SESSION['id'] ?? '');
        $providerId = (int) ($this->post['providerId'] ?? 0);

        if ($memberId !== '' && $providerId > 0 && CSRFtoken::verify((string) ($this->post['csrftoken'] ?? ''))) {
            try {
                $pdo = DBConnection::getPdo();
                $local = $pdo->prepare('SELECT password FROM Member WHERE id = :id AND password <> "" LIMIT 1');
                $local->execute(['id' => $memberId]);
                $hasLocal = $local->fetchColumn() !== false;

                $count = $pdo->prepare(
                    'SELECT COUNT(*) FROM member_external_identity WHERE member_id = :id AND auth_provider_id <> :pid'
                );
                $count->execute(['id' => $memberId, 'pid' => $providerId]);
                $remainingExternal = (int) $count->fetchColumn();

                if ($hasLocal || $remainingExternal > 0) {
                    $delete = $pdo->prepare(
                        'DELETE FROM member_external_identity WHERE member_id = :id AND auth_provider_id = :pid'
                    );
                    $delete->execute(['id' => $memberId, 'pid' => $providerId]);
                    header('Location: ./mypage/start/?auth=unlinked', true, 303);
                    return new EmptyView();
                }
            } catch (\Throwable $e) {
                if (function_exists('error_log')) {
                    error_log('[saso-auth-unlink] '.$e->getMessage());
                }
            }
        }

        header('Location: ./mypage/start/?auth=blocked', true, 303);
        return new EmptyView();
    }
}
