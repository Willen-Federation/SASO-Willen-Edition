<?php

declare(strict_types=1);

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;

/**
 * `GET /mypage/passkeyComplete/`
 *
 * Reserved for direct hits while a passkey enrollment is in flight.
 *
 * The actual OIDC callback for Auth0 lands on `/auth/callback`, where the
 * `auth.purpose === 'passkey_enroll'` branch in `index.php` handles the
 * code exchange and redirects the user to `/mypage/start/?passkey=enrolled`.
 * This DI container only catches the rare case where a stale tab returns
 * to `/mypage/passkeyComplete/` directly — we just send the user back to
 * My Page so they see the current list and any status banner.
 */
final class PasskeyCompleteDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
    }

    public function flow(): View
    {
        \saso\util\Redirect::redirect('mypage/start/');
        return new EmptyView();
    }
}
