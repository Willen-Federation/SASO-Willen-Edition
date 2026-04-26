<?php
namespace saso\auth;

use saso\common;
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
            $this->ctrl = new AuthController($query);
            $this->usecase = new common\EmptyUsecase(
                new AuthPresenter(
                    new AuthView(),
                )
            );
        } else {
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
}
