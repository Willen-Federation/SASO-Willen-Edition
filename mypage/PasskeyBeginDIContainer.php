<?php

declare(strict_types=1);

namespace saso\mypage;

use saso\common\EmptyView;
use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DBConnection;
use saso\util\CSRFtoken;
use Saso\Application\MyPage\Passkey\Auth0ProviderLookup;
use Saso\Application\MyPage\Passkey\PasskeyEnrollmentUrlBuilder;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;

/**
 * `POST /mypage/passkeyBegin/`
 *
 * Kicks off Auth0-side passkey enrollment for the currently logged-in
 * SASO member. Validates CSRF, finds the Auth0 link (member must have
 * already signed in with Auth0 at least once), then builds an
 * `https://{tenant}/authorize?prompt=login&...` URL and redirects.
 *
 * The matching callback is the standard `/auth/callback` short-circuit
 * in `index.php`, which recognises `auth.purpose === 'passkey_enroll'`
 * and routes back to `/mypage/start/?passkey=enrolled` without altering
 * the existing session id (the user is already signed in — this is a
 * re-auth to add a factor, not a login).
 */
final class PasskeyBeginDIContainer implements DIContainer
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
        $memberId = (string) ($_SESSION['id'] ?? '');
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

        try {
            $pdo    = DBConnection::getPdo();
            $lookup = new Auth0ProviderLookup($pdo);
            $link   = $lookup->findFor($memberId);
            if ($link === null) {
                \saso\util\Redirect::redirect('mypage/start/?passkey=no_auth0_link');
                return new EmptyView();
            }

            $appKey = (string) (getenv('APP_KEY') ?: '');
            $rawKey = base64_decode($appKey, true);
            if ($rawKey === false || strlen($rawKey) !== 32) {
                throw new \RuntimeException('APP_KEY must be a base64-encoded 32-byte value.');
            }
            $encryptor = new SecretEncryptor($rawKey);
            $providers = new PdoAuthProviderRepository($pdo, $encryptor);

            $callbackUrl = $this->callbackUrl();
            $builder     = new PasskeyEnrollmentUrlBuilder($providers, $callbackUrl);
            $url         = $builder->build($link->providerId, $memberId, '/mypage/start/?passkey=enrolled');

            header('Location: '.$url, true, 302);
            return new EmptyView();
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-passkey-begin] '.$e->getMessage());
            }
            \saso\util\Redirect::redirect('mypage/start/?passkey=error');
            return new EmptyView();
        }
    }

    private function callbackUrl(): string
    {
        $onHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $onHttps ? 'https://' : 'http://';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme.$host.'/auth/callback';
    }
}
