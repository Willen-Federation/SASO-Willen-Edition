<?php

declare(strict_types=1);

namespace saso\mypage;

use GuzzleHttp\Client;
use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\CSRFtoken;
use Saso\Application\MyPage\Passkey\Auth0ManagementApiException;
use Saso\Application\MyPage\Passkey\Auth0PasskeyConfig;
use Saso\Application\MyPage\Passkey\Auth0PasskeyService;
use Saso\Application\MyPage\Passkey\Auth0ProviderLookup;
use Saso\Infrastructure\Auth\GuzzleAuth0ManagementApi;

/**
 * `POST /mypage/passkeyDelete/`
 *
 * Removes one passkey from Auth0. CSRF-protected; the Auth0
 * `authentication_method_id` is taken from the form submission and the
 * Auth0 `sub` is resolved server-side from the current session's member
 * id, so a CSRF-bypassing attacker still cannot point this at someone
 * else's account.
 */
final class PasskeyDeleteDIContainer implements DIContainer
{
    /** @var array<string, mixed> */
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
        $memberId  = (string) ($_SESSION['id'] ?? '');
        $passkeyId = trim((string) ($this->post['passkey_id'] ?? ''));

        if ($memberId === '') {
            \saso\util\Redirect::redirect('mypage/start/?passkey=unauthenticated');
            return new EmptyView();
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
            || !CSRFtoken::verify((string) ($this->post['csrftoken'] ?? ''))
        ) {
            \saso\util\Redirect::redirect('mypage/start/?passkey=invalid_csrf');
            return new EmptyView();
        }
        if ($passkeyId === '') {
            \saso\util\Redirect::redirect('mypage/start/?passkey=invalid_id');
            return new EmptyView();
        }

        try {
            $pdo    = DBConnection::getPdo();
            $lookup = new Auth0ProviderLookup($pdo);
            $config = Auth0PasskeyConfig::fromEnv();
            if ($config === null) {
                \saso\util\Redirect::redirect('mypage/start/?passkey=m2m_unavailable');
                return new EmptyView();
            }

            $api = new GuzzleAuth0ManagementApi(
                new Client(['timeout' => 10.0, 'connect_timeout' => 5.0]),
                $config->domain,
                $config->clientId,
                $config->clientSecret,
            );
            $service = new Auth0PasskeyService($lookup, $api);
            $ok      = $service->deleteFor($memberId, $passkeyId);

            \saso\util\Redirect::redirect(
                $ok ? 'mypage/start/?passkey=deleted' : 'mypage/start/?passkey=no_auth0_link'
            );
            return new EmptyView();
        } catch (Auth0ManagementApiException $e) {
            if (function_exists('error_log')) {
                error_log('[saso-passkey-delete] Auth0 '.$e->upstreamStatus.': '.$e->getMessage());
            }
            \saso\util\Redirect::redirect('mypage/start/?passkey=delete_failed');
            return new EmptyView();
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-passkey-delete] '.$e->getMessage());
            }
            \saso\util\Redirect::redirect('mypage/start/?passkey=error');
            return new EmptyView();
        }
    }
}
