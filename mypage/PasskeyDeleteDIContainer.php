<?php

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;

final class PasskeyDeleteDIContainer implements DIContainer
{
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void {}
    public function flow(): View
    {
        // Passkey credential management disabled while the WebAuthn flow is
        // unsafe — see GitHub issue #203.
        \saso\util\Redirect::redirect('mypage/start/?passkey=disabled');
        return new EmptyView();
    }
}
