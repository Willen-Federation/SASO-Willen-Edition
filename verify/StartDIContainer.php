<?php
namespace saso\verify;

use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;

final class StartDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl    = new StartController();
        $this->usecase = new EmptyUsecase(
            new StartPresenter(
                new StartView(),
            ),
        );
    }
}
