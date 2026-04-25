<?php
namespace saso\installer;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;

final class InstallDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailView();
        $this->ctrl = new InstallController($post);
        $this->usecase = new InstallUsecase(
            new InstallPresenter(
                new InstallView(),
                new  common\RegisterFailView('installer/start'),
            ),
        );
    }
}