<?php
namespace saso\auth;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;

final class PasswordDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new PasswordView(
            array_key_exists('changed', $query),
            array_key_exists('errorNow', $query),
        );
        $this->ctrl = new PasswordController($post);
        $this->usecase = new ChangePasswordUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            new PasswordPresenter(
                new common\RegisterSuccessView(),
                new common\RegisterFailView('start/password'),
            ),
        );
    }
}
