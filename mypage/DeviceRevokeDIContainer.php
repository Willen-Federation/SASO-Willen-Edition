<?php

namespace saso\mypage;

use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\CSRFtoken;
use saso\util\Redirect;

/**
 * POST /mypage/deviceRevoke
 *
 * Self-service device revocation. Loads the target device_token row, refuses
 * to act unless its member_id matches the caller's session, and then revokes
 * via the existing domain `DeviceToken::revoke()` immutable transition.
 *
 * Returns a 303 redirect back to /mypage/ on every outcome (success, failure,
 * not-found) so the browser falls back to the rendered list view — keeps the
 * UX identical to the existing unlinkProvider flow.
 */
final class DeviceRevokeDIContainer implements DIContainer
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
        $deviceId = (int) ($this->post['device_id'] ?? 0);
        $csrf     = (string) ($this->post['csrftoken'] ?? '');

        if ($memberId === '' || $deviceId < 1 || !CSRFtoken::verify($csrf)) {
            Redirect::redirect('mypage/start/?device=blocked');
            return new EmptyView();
        }

        try {
            $repo  = new PdoDeviceTokenRepository(DBConnection::getPdo());
            $token = $repo->findById($deviceId);

            if ($token === null || $token->memberId !== $memberId) {
                Redirect::redirect('mypage/start/?device=notfound');
                return new EmptyView();
            }

            if (!$token->revoked) {
                $repo->save($token->revoke());
            }

            Redirect::redirect('mypage/start/?device=revoked');
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-mypage-device-revoke] '.$e->getMessage());
            }
            Redirect::redirect('mypage/start/?device=error');
        }

        return new EmptyView();
    }
}
