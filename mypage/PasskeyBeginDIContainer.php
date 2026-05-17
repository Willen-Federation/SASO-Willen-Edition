<?php

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;

final class PasskeyBeginDIContainer implements DIContainer
{
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void {}
    public function flow(): View
    {
        // Passkey registration disabled. The legacy implementation stored the
        // raw attestationObject without verifying it, so credentials minted
        // through this path could not be cryptographically trusted at sign-in.
        // See GitHub issue #203 for the requirements before re-enabling.
        http_response_code(410);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'passkey_disabled']);
        return new EmptyView();
    }
}
