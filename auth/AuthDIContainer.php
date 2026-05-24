<?php
namespace saso\auth;

use saso\common;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;

final class AuthDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        if(empty($post)) {
            $pdo = \saso\repository\DBConnection::getPdo();
            $encryptor = self::buildEncryptor();
            $repo = new \Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository($pdo, $encryptor);

            $this->ctrl = new AuthController($query);
            $this->usecase = new AuthUsecase(
                $repo,
                new AuthPresenter(
                    new AuthView(),
                )
            );
        } else {
            // Login CSRF guard. UserCompiler's global token check fires only
            // when $authed is true; for the unauthenticated login POST that
            // gate is silent, so we enforce it here. Without this, an active
            // attacker could trick the browser into submitting the form with
            // attacker-supplied credentials, signing the victim into the
            // attacker's account (login-CSRF) and harvesting follow-up state.
            if (!\saso\util\CSRFtoken::verify((string) ($post['csrftoken'] ?? ''))) {
                // Re-render the login form with the error banner instead of
                // dying with a raw 'invalid csrftoken.' string: the operator
                // expects the same UX as a wrong-password submission.
                $pdo = \saso\repository\DBConnection::getPdo();
                $encryptor = self::buildEncryptor();
                $repo = new \Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository($pdo, $encryptor);
                $errorQuery = $query;
                $errorQuery['error'] = '1';
                $this->ctrl = new AuthController($errorQuery);
                $this->usecase = new AuthUsecase(
                    $repo,
                    new AuthPresenter(
                        new AuthView(),
                    )
                );
                return;
            }

            $this->ctrl = new AuthController($query, new LoginController($post));
            $this->usecase = new LoginUsecase(
                new DbFinder(),
                new DbUpdater(),
                new LoginPresenter(
                    new LoginView(),
                )
            );
        }
    }

    private static function buildEncryptor(): \Saso\Infrastructure\Auth\Crypto\SecretEncryptor
    {
        return AppKeyResolver::encryptor();
    }
}
